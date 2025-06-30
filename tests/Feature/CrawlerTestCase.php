<?php

/**
 * CrawlerTestCase.php
 *
 * Base test case for website crawling tests using Spatie/Crawler
 * This class provides shared crawling logic and data providers
 * to ensure crawling happens only once and results are reused
 * across all test methods.
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

use LibreNMS\Tests\TestCase;
use LibreNMS\Traits\RuntimeClassCache;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\CrawlProfiles\CrawlProfile;

abstract class CrawlerTestCase extends TestCase
{
    use RuntimeClassCache;

    /** @var array Shared crawl results cache */
    private static array $crawlResults = [];

    /** @var bool Whether crawling has been performed */
    private static bool $crawled = false;

    /** @var array Default crawl configuration */
    protected array $crawlConfig = [
        'base_url' => 'http://localhost',
        'max_depth' => 3,
        'delay_between_requests' => 0,
        'timeout' => 30,
        'concurrent_requests' => 1,
        'ignore_robots' => true,
        'user_agent' => 'LibreNMS Test Crawler',
    ];

    /**
     * Get the base URL for crawling
     * Override this method to provide the URL to crawl
     */
    abstract protected function getBaseUrl(): string;

    /**
     * Get crawl configuration
     * Override this method to customize crawl settings
     */
    protected function getCrawlConfig(): array
    {
        return array_merge($this->crawlConfig, [
            'base_url' => $this->getBaseUrl(),
        ]);
    }

    /**
     * Get custom crawl profile
     * Override this method to provide custom crawling rules
     */
    protected function getCrawlProfile(): ?CrawlProfile
    {
        return null;
    }

    /**
     * Perform the actual crawling if not already done
     */
    protected function performCrawling(): void
    {
        if (self::$crawled) {
            return;
        }

        $config = $this->getCrawlConfig();
        $baseUrl = $config['base_url'];

        $crawler = Crawler::create([
            'timeout' => $config['timeout'],
            'delay_between_requests' => $config['delay_between_requests'],
            'concurrent_requests' => $config['concurrent_requests'],
        ]);

        $crawler->setUserAgent($config['user_agent']);

        if ($config['ignore_robots']) {
            $crawler->ignoreRobots();
        }

        $crawler->setMaximumDepth($config['max_depth']);

        // Set custom crawl profile if provided
        $profile = $this->getCrawlProfile();
        if ($profile) {
            $crawler->setCrawlProfile($profile);
        }

        $observer = new TestCrawlObserver();
        $crawler->setCrawlObserver($observer);

        try {
            $crawler->startCrawling($baseUrl);
            self::$crawlResults = $observer->getResults();
            self::$crawled = true;
        } catch (\Exception $e) {
            $this->markTestSkipped("Crawling failed: {$e->getMessage()}");
        }
    }

    /**
     * Get all crawled URLs and their responses
     */
    protected function getCrawlResults(): array
    {
        $this->performCrawling();

        return self::$crawlResults;
    }

    /**
     * Data provider for successful crawled URLs
     */
    public static function successfulUrlsProvider(): array
    {
        // This will be populated by the first test that runs
        if (empty(self::$crawlResults)) {
            // Create a dummy instance to trigger crawling
            $class = get_called_class();
            $instance = new $class();
            $instance->performCrawling();
        }

        $data = [];
        foreach (self::$crawlResults as $url => $result) {
            if ($result['status_code'] >= 200 && $result['status_code'] < 300) {
                $data[$url] = [$url, $result];
            }
        }

        return $data;
    }

    /**
     * Data provider for all crawled URLs
     */
    public static function allUrlsProvider(): array
    {
        // This will be populated by the first test that runs
        if (empty(self::$crawlResults)) {
            // Create a dummy instance to trigger crawling
            $class = get_called_class();
            $instance = new $class();
            $instance->performCrawling();
        }

        $data = [];
        foreach (self::$crawlResults as $url => $result) {
            $data[$url] = [$url, $result];
        }

        return $data;
    }

    /**
     * Data provider for failed crawled URLs
     */
    public static function failedUrlsProvider(): array
    {
        // This will be populated by the first test that runs
        if (empty(self::$crawlResults)) {
            // Create a dummy instance to trigger crawling
            $class = get_called_class();
            $instance = new $class();
            $instance->performCrawling();
        }

        $data = [];
        foreach (self::$crawlResults as $url => $result) {
            if ($result['status_code'] >= 400) {
                $data[$url] = [$url, $result];
            }
        }

        return $data;
    }

    /**
     * Reset crawl results for testing
     */
    protected function resetCrawlResults(): void
    {
        self::$crawlResults = [];
        self::$crawled = false;
    }

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Check if crawling is enabled
        if (! $this->isCrawlingEnabled()) {
            $this->markTestSkipped('Crawler tests are disabled. Set CRAWLER_TESTS=1 to enable.');
        }
    }

    /**
     * Check if crawling tests are enabled
     */
    protected function isCrawlingEnabled(): bool
    {
        return (bool) getenv('CRAWLER_TESTS');
    }

    /**
     * Creates the application (required by Laravel TestCase)
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';

        return $app;
    }
}

/**
 * Custom crawl observer to collect crawl results
 */
class TestCrawlObserver extends CrawlObserver
{
    private array $results = [];

    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null): void
    {
        $this->results[(string) $url] = [
            'status_code' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
            'found_on_url' => $foundOnUrl ? (string) $foundOnUrl : null,
            'response_time' => $response->hasHeader('X-Response-Time')
                ? $response->getHeaderLine('X-Response-Time')
                : null,
        ];
    }

    public function crawlFailed(UriInterface $url, \Exception $exception, ?UriInterface $foundOnUrl = null): void
    {
        $this->results[(string) $url] = [
            'status_code' => 0,
            'headers' => [],
            'body' => '',
            'found_on_url' => $foundOnUrl ? (string) $foundOnUrl : null,
            'error' => $exception->getMessage(),
            'response_time' => null,
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
