<?php

/**
 * RbacCrawlerTest.php
 *
 * Crawls all discoverable pages as a restricted user and verifies:
 * 1. No pages return 500 errors (broken permission handling)
 * 2. Data from devices the user doesn't have access to never leaks into responses
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 */

namespace LibreNMS\Tests\Feature;

use App\Models\Device;
use App\Models\Port;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('rbac')]
class RbacCrawlerTest extends DBTestCase
{
    use DatabaseTransactions;

    /** @var array<string, true> URLs already visited */
    private array $visited = [];

    /** @var string[] URL queue to crawl */
    private array $queue = [];

    /** @var array<int, array{url: string, status: int|string}> Pages that returned 5xx */
    private array $serverErrors = [];

    /** @var array<int, array{url: string, pattern: string}> Pages where forbidden data appeared */
    private array $rbacLeaks = [];

    /** @var string[] Strings that must never appear in responses */
    private array $forbiddenPatterns = [];

    /** @var int Maximum number of pages to crawl before stopping */
    private int $maxPages = 500;

    /** @var array<string, int> Status code summary for reporting */
    private array $statusSummary = [];

    /**
     * URLs (substrings) to skip -- destructive actions, auth endpoints, etc.
     */
    private array $skipPatterns = [
        '/logout',
        '/login',
        '/delhost',
        '/addhost',
        '/addsrv',
        'delete',
        '/install',
        '/debug',
        '2fa',
        'password',
        '/register',
        'rediscover',
    ];

    public function testRestrictedUserCannotSeeForbiddenDevices(): void
    {
        // --- Setup: create two devices with distinctive, searchable hostnames ---
        $allowedDevice = Device::factory()->create([
            'hostname' => 'rbac-allowed-host.test.example',
            'sysName' => 'rbac-allowed-sysname',
        ]);

        $forbiddenDevice = Device::factory()->create([
            'hostname' => 'rbac-forbidden-host.test.example',
            'sysName' => 'rbac-forbidden-sysname',
        ]);

        // Create ports on both devices so port-listing pages have data to show/leak
        Port::factory()->create([
            'device_id' => $allowedDevice->device_id,
            'ifDescr' => 'GigabitEthernet0/0',
            'ifAlias' => 'rbac-allowed-port-alias',
            'ifName' => 'Gi0/0',
        ]);

        Port::factory()->create([
            'device_id' => $forbiddenDevice->device_id,
            'ifDescr' => 'GigabitEthernet0/1',
            'ifAlias' => 'rbac-forbidden-port-alias',
            'ifName' => 'Gi0/1',
        ]);

        // --- Create a restricted user with access to only the allowed device ---
        $user = User::factory()->create();
        $user->devicesOwned()->attach($allowedDevice->device_id);

        // Patterns that should NEVER appear in responses for this user
        $this->forbiddenPatterns = [
            $forbiddenDevice->hostname,
            $forbiddenDevice->sysName,
            'rbac-forbidden-port-alias',
        ];

        // --- Build seed URLs ---
        $this->seedUrls($allowedDevice);

        // --- Crawl ---
        $this->actingAs($user);

        while (! empty($this->queue) && count($this->visited) < $this->maxPages) {
            $url = array_shift($this->queue);
            $this->crawlUrl($url);
        }

        // --- Report ---
        $this->reportResults();
    }

    public function testNoPagesReturn500ForAdmin(): void
    {
        // Create a device so pages have some data to render
        $device = Device::factory()->create([
            'hostname' => 'rbac-admin-test-host.test.example',
            'sysName' => 'rbac-admin-test-sysname',
        ]);

        Port::factory()->create([
            'device_id' => $device->device_id,
            'ifDescr' => 'GigabitEthernet0/0',
            'ifAlias' => 'admin-test-port',
            'ifName' => 'Gi0/0',
        ]);

        $admin = User::factory()->admin()->create();

        // No forbidden patterns -- we only check for 500 errors
        $this->forbiddenPatterns = [];

        $this->seedUrls($device);
        $this->actingAs($admin);

        while (! empty($this->queue) && count($this->visited) < $this->maxPages) {
            $url = array_shift($this->queue);
            $this->crawlUrl($url);
        }

        // Report only server errors
        $errorMessages = [];

        if (! empty($this->serverErrors)) {
            $lines = array_map(
                fn ($e) => "  [{$e['status']}] {$e['url']}",
                $this->serverErrors
            );
            $errorMessages[] = 'Server errors (5xx) found on ' . count($this->serverErrors) . " pages:\n" . implode("\n", $lines);
        }

        echo "\n[Admin Crawl] Visited " . count($this->visited) . ' pages. Status summary: ' . json_encode($this->statusSummary) . "\n";

        $this->assertEmpty($errorMessages, implode("\n\n", $errorMessages));
    }

    /**
     * Seed the crawl queue with starting URLs.
     */
    private function seedUrls(Device $device): void
    {
        // Top-level modern routes
        $seeds = [
            '/',
            '/overview',
            '/about',
            '/outages',
            '/vlans',
            '/inventory',
            '/nac',
        ];

        // Legacy pages (discovered from includes/html/pages/)
        $legacyPages = [
            'alerts',
            'alert-log',
            'alert-rules',
            'alert-schedule',
            'alert-stats',
            'alert-transports',
            'api-access',
            'apps',
            'bills',
            'customers',
            'device-dependencies',
            'devices',
            'eventlog',
            'graphs',
            'graylog',
            'iftype',
            'notifications',
            'packages',
            'peering',
            'ports',
            'pseudowires',
            'routing',
            'search',
            'services',
            'syslog',
            'tools',
        ];

        foreach ($legacyPages as $page) {
            $seeds[] = '/' . $page;
        }

        // Device-specific pages with various tabs
        $deviceTabs = [
            '',           // overview (default)
            'tab=ports',
            'tab=health',
            'tab=apps',
            'tab=processes',
            'tab=alerts',
            'tab=routing',
            'tab=graphs',
            'tab=neighbours',
            'tab=vlans',
            'tab=mef',
            'tab=collectd',
            'tab=munin',
            'tab=nac',
            'tab=notes',
        ];

        foreach ($deviceTabs as $tab) {
            $url = '/device/' . $device->device_id;
            if ($tab) {
                $url .= '/' . $tab;
            }
            $seeds[] = $url;
        }

        // Device log pages
        $seeds[] = "/device/{$device->device_id}/logs/eventlog";
        $seeds[] = "/device/{$device->device_id}/logs/syslog";

        // Modern route pages
        $seeds[] = '/health';
        $seeds[] = '/wireless/afreq';
        $seeds[] = '/poller';
        $seeds[] = '/poller/log';
        $seeds[] = '/poller/performance';

        foreach ($seeds as $url) {
            $this->addToQueue($url);
        }
    }

    /**
     * Fetch a URL, check for errors and RBAC leaks, extract links.
     */
    private function crawlUrl(string $url): void
    {
        $normalized = $this->normalizeUrl($url);

        if (isset($this->visited[$normalized])) {
            return;
        }

        $this->visited[$normalized] = true;

        try {
            $response = $this->get($url);
        } catch (\Throwable $e) {
            $this->serverErrors[] = ['url' => $url, 'status' => 'exception: ' . substr($e->getMessage(), 0, 200)];

            return;
        }

        $status = $response->getStatusCode();
        $statusGroup = (string) intdiv($status, 100) . 'xx';
        $this->statusSummary[$statusGroup] = ($this->statusSummary[$statusGroup] ?? 0) + 1;

        // Track server errors
        if ($status >= 500) {
            $this->serverErrors[] = ['url' => $url, 'status' => $status];
        }

        // Check for RBAC leaks in successful responses
        if ($status >= 200 && $status < 400) {
            $content = $response->getContent();

            foreach ($this->forbiddenPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $this->rbacLeaks[] = ['url' => $url, 'pattern' => $pattern];
                }
            }

            // Extract and queue links for crawling
            $this->extractLinks($content);
        }
    }

    /**
     * Extract href links from HTML content and add them to the queue.
     */
    private function extractLinks(string $content): void
    {
        if (preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches)) {
            foreach ($matches[1] as $link) {
                $this->addToQueue($link);
            }
        }
    }

    /**
     * Add a URL to the crawl queue if it's a valid internal link.
     */
    private function addToQueue(string $url): void
    {
        // Handle absolute URLs -- only follow if same host
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $parsed = parse_url($url);
            $host = $parsed['host'] ?? '';

            // Only follow localhost / 127.0.0.1 links (test environment)
            if ($host !== 'localhost' && $host !== '127.0.0.1' && $host !== '') {
                return;
            }

            $url = ($parsed['path'] ?? '/');
            if (isset($parsed['query'])) {
                $url .= '?' . $parsed['query'];
            }
        }

        // Must start with /
        if (! str_starts_with($url, '/')) {
            return;
        }

        // Skip non-navigable links
        if (str_starts_with($url, '#')
            || str_starts_with($url, 'javascript:')
            || str_starts_with($url, 'mailto:')
            || str_starts_with($url, 'data:')
        ) {
            return;
        }

        // Skip static assets
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|map)(\?|$)/i', $url)) {
            return;
        }

        // Skip dangerous / destructive URLs
        foreach ($this->skipPatterns as $skip) {
            if (stripos($url, $skip) !== false) {
                return;
            }
        }

        // Skip graph URLs (they generate images, not HTML)
        if (str_starts_with($url, '/graph')) {
            return;
        }

        $normalized = $this->normalizeUrl($url);

        if (! isset($this->visited[$normalized])) {
            // Avoid duplicates in queue
            if (! in_array($normalized, array_map([$this, 'normalizeUrl'], $this->queue))) {
                $this->queue[] = $url;
            }
        }
    }

    /**
     * Normalize a URL for deduplication.
     */
    private function normalizeUrl(string $url): string
    {
        // Remove fragment
        $url = strtok($url, '#') ?: '/';

        // Remove trailing slash (but keep root)
        return $url === '/' ? '/' : rtrim($url, '/');
    }

    /**
     * Build assertion message and fail if there are issues.
     */
    private function reportResults(): void
    {
        $errorMessages = [];

        if (! empty($this->serverErrors)) {
            $lines = array_map(
                fn ($e) => "  [{$e['status']}] {$e['url']}",
                $this->serverErrors
            );
            $errorMessages[] = 'Server errors (5xx) found on ' . count($this->serverErrors) . " pages:\n" . implode("\n", $lines);
        }

        if (! empty($this->rbacLeaks)) {
            $lines = array_map(
                fn ($l) => "  {$l['url']} -- leaked pattern: \"{$l['pattern']}\"",
                $this->rbacLeaks
            );
            $errorMessages[] = 'RBAC leaks found on ' . count($this->rbacLeaks) . " pages:\n" . implode("\n", $lines);
        }

        // Print summary regardless of pass/fail
        echo "\n[RBAC Crawl] Visited " . count($this->visited) . ' pages. Status summary: ' . json_encode($this->statusSummary) . "\n";

        if (! empty($this->rbacLeaks)) {
            echo '[RBAC Crawl] Leaked on these URLs:' . "\n";
            foreach ($this->rbacLeaks as $leak) {
                echo "  {$leak['url']} -- \"{$leak['pattern']}\"\n";
            }
        }

        $this->assertEmpty($errorMessages, implode("\n\n", $errorMessages));
    }
}
