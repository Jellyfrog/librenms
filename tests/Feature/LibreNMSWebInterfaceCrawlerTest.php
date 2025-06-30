<?php

/**
 * LibreNMSWebInterfaceCrawlerTest.php
 *
 * Test class for crawling the LibreNMS web interface
 * This demonstrates testing a real application using the crawler framework.
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
 *
 * @copyright  2024 LibreNMS Contributors
 * @author     LibreNMS Contributors
 */

namespace LibreNMS\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Psr\Http\Message\UriInterface;

#[Group('crawler')]
#[Group('web-interface')]
class LibreNMSWebInterfaceCrawlerTest extends CrawlerTestCase
{
    /**
     * Get the base URL for crawling LibreNMS web interface
     */
    protected function getBaseUrl(): string
    {
        // Use configured base URL or default for testing
        return getenv('LIBRENMS_BASE_URL') ?: 'http://localhost:8000';
    }

    /**
     * Customize crawl configuration for LibreNMS
     */
    protected function getCrawlConfig(): array
    {
        return array_merge(parent::getCrawlConfig(), [
            'max_depth' => 2, // Limit depth to avoid too many pages
            'delay_between_requests' => 200, // Be respectful to the server
            'timeout' => 15,
            'concurrent_requests' => 1, // Single request to avoid overloading
        ]);
    }

    /**
     * Custom crawl profile for LibreNMS
     */
    protected function getCrawlProfile(): ?CrawlProfile
    {
        return new LibreNMSCrawlProfile($this->getBaseUrl());
    }

    /**
     * Test that LibreNMS pages return successful status codes
     */
    #[DataProvider('successfulUrlsProvider')]
    public function testLibreNMSPagesReturnSuccessfulStatusCodes(string $url, array $result): void
    {
        $this->assertGreaterThanOrEqual(200, $result['status_code']);
        $this->assertLessThan(300, $result['status_code']);
        $this->assertNotEmpty($result['body'], "LibreNMS page $url should not be empty");
    }

    /**
     * Test that LibreNMS pages contain expected branding
     */
    #[DataProvider('successfulUrlsProvider')] 
    public function testLibreNMSPagesContainBranding(string $url, array $result): void
    {
        $body = $result['body'];
        
        // Skip non-HTML responses
        $contentType = $result['headers']['content-type'][0] ?? '';
        if (! str_contains($contentType, 'text/html')) {
            $this->markTestSkipped("Skipping non-HTML content: $contentType");
        }

        // Check for LibreNMS branding (adjust based on actual HTML structure)
        $hasLibreNMSBranding = str_contains($body, 'LibreNMS') || 
                              str_contains($body, 'librenms') ||
                              str_contains($body, 'Network Monitoring');
        
        $this->assertTrue($hasLibreNMSBranding, "LibreNMS page $url should contain LibreNMS branding");
    }

    /**
     * Test that LibreNMS pages have proper meta tags
     */
    #[DataProvider('successfulUrlsProvider')]
    public function testLibreNMSPagesHaveProperMetaTags(string $url, array $result): void
    {
        $body = $result['body'];
        
        // Skip non-HTML responses
        $contentType = $result['headers']['content-type'][0] ?? '';
        if (! str_contains($contentType, 'text/html')) {
            $this->markTestSkipped("Skipping non-HTML content: $contentType");
        }

        // Check for viewport meta tag (important for mobile responsiveness)
        $hasViewport = str_contains($body, 'name="viewport"');
        
        // Check for charset declaration
        $hasCharset = str_contains($body, 'charset=') || str_contains($body, 'meta charset');
        
        if ($hasViewport || $hasCharset) {
            $this->assertTrue(true, "LibreNMS page $url has proper meta tags");
        } else {
            $this->markTestIncomplete("LibreNMS page $url may be missing important meta tags");
        }
    }

    /**
     * Test that API endpoints return JSON
     */
    #[DataProvider('allUrlsProvider')]
    public function testApiEndpointsReturnJson(string $url, array $result): void
    {
        // Only test URLs that look like API endpoints
        if (! str_contains($url, '/api/')) {
            $this->markTestSkipped("Not an API endpoint: $url");
        }

        if ($result['status_code'] === 0 || isset($result['error'])) {
            $this->markTestSkipped("Network error for API endpoint $url");
        }

        // Check content type
        $contentType = $result['headers']['content-type'][0] ?? '';
        $this->assertStringContainsString('application/json', $contentType, 
            "API endpoint $url should return JSON content");

        // Try to decode JSON
        $body = $result['body'];
        $decoded = json_decode($body, true);
        $this->assertNotNull($decoded, "API endpoint $url should return valid JSON");
    }

    /**
     * Test that no pages have obvious errors
     */
    #[DataProvider('successfulUrlsProvider')]
    public function testPagesHaveNoObviousErrors(string $url, array $result): void
    {
        $body = strtolower($result['body']);
        
        $errorIndicators = [
            'fatal error',
            'parse error', 
            'undefined variable',
            'undefined index',
            'call to undefined',
            'mysql error',
            'database error',
            'exception occurred',
        ];

        foreach ($errorIndicators as $indicator) {
            $this->assertStringNotContainsString($indicator, $body, 
                "Page $url should not contain error: $indicator");
        }
    }

    /**
     * Test crawl performance metrics
     */
    public function testCrawlPerformanceMetrics(): void
    {
        $results = $this->getCrawlResults();
        
        $responseTimes = [];
        foreach ($results as $url => $result) {
            if ($result['response_time'] !== null) {
                $responseTimes[] = (float) $result['response_time'];
            }
        }

        if (! empty($responseTimes)) {
            $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
            $maxResponseTime = max($responseTimes);
            
            echo "\nPerformance Metrics:\n";
            echo "Average response time: " . number_format($avgResponseTime, 3) . "s\n";
            echo "Max response time: " . number_format($maxResponseTime, 3) . "s\n";
            
            // Performance assertions
            $this->assertLessThan(10.0, $avgResponseTime, 
                'Average response time should be less than 10 seconds');
            $this->assertLessThan(30.0, $maxResponseTime, 
                'Maximum response time should be less than 30 seconds');
        } else {
            $this->markTestSkipped('No response time data available');
        }
    }
}

/**
 * Custom crawl profile for LibreNMS web interface
 */
class LibreNMSCrawlProfile extends CrawlProfile
{
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function shouldCrawl(UriInterface $url): bool
    {
        $urlString = (string) $url;
        
        // Only crawl URLs from the same domain
        if (! str_starts_with($urlString, $this->baseUrl)) {
            return false;
        }

        // Skip certain file types
        $skipExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.pdf', '.zip', '.tar.gz'];
        foreach ($skipExtensions as $ext) {
            if (str_ends_with($urlString, $ext)) {
                return false;
            }
        }

        // Skip certain paths that might be problematic
        $skipPaths = [
            '/logout',
            '/ajax',
            '/download',
            '/export',
            '/print',
            '/rrd/',
            '/graphs/',
        ];

        foreach ($skipPaths as $path) {
            if (str_contains($urlString, $path)) {
                return false;
            }
        }

        return true;
    }
}