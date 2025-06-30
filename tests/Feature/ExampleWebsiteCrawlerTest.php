<?php

/**
 * ExampleWebsiteCrawlerTest.php
 *
 * Example test class demonstrating the usage of CrawlerTestCase
 * This shows how to implement various types of tests against
 * crawled website pages using data providers.
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
use Spatie\Crawler\CrawlProfiles\CrawlInternalUrls;

#[Group('crawler')]
#[Group('external-dependencies')]
class ExampleWebsiteCrawlerTest extends CrawlerTestCase
{
    /**
     * Get the base URL for crawling
     */
    protected function getBaseUrl(): string
    {
        // Use environment variable or default to a test URL
        return getenv('CRAWLER_BASE_URL') ?: 'http://httpbin.org';
    }

    /**
     * Customize crawl configuration
     */
    protected function getCrawlConfig(): array
    {
        return array_merge(parent::getCrawlConfig(), [
            'max_depth' => 2,
            'delay_between_requests' => 100, // 100ms delay
            'timeout' => 10,
            'concurrent_requests' => 2,
        ]);
    }

    /**
     * Get custom crawl profile to limit crawling scope
     */
    protected function getCrawlProfile(): ?CrawlProfile
    {
        return new CrawlInternalUrls($this->getBaseUrl());
    }

    /**
     * Test that all successfully crawled pages return expected HTTP status codes
     */
    #[DataProvider('successfulUrlsProvider')]
    public function testSuccessfulPagesReturnValidStatusCodes(string $url, array $result): void
    {
        $this->assertGreaterThanOrEqual(200, $result['status_code']);
        $this->assertLessThan(300, $result['status_code']);
        $this->assertNotEmpty($result['body'], "Page $url should not be empty");
    }

    /**
     * Test that all pages have valid HTML structure
     */
    #[DataProvider('successfulUrlsProvider')]
    public function testPagesHaveValidHtmlStructure(string $url, array $result): void
    {
        $body = $result['body'];
        
        // Skip non-HTML responses
        $contentType = $result['headers']['content-type'][0] ?? '';
        if (! str_contains($contentType, 'text/html')) {
            $this->markTestSkipped("Skipping non-HTML content: $contentType");
        }

        // Basic HTML structure checks
        $this->assertStringContainsString('<html', $body, "Page $url should contain HTML tag");
        
        // Check for basic HTML elements
        $hasHead = str_contains($body, '<head') || str_contains($body, '<HEAD');
        $hasBody = str_contains($body, '<body') || str_contains($body, '<BODY');
        
        $this->assertTrue($hasHead || $hasBody, "Page $url should have either head or body tag");
    }

    /**
     * Test that pages have reasonable response times
     */
    #[DataProvider('successfulUrlsProvider')]
    public function testPagesHaveReasonableResponseTimes(string $url, array $result): void
    {
        if ($result['response_time'] === null) {
            $this->markTestSkipped("Response time not available for $url");
        }

        $responseTime = (float) $result['response_time'];
        $this->assertLessThan(5.0, $responseTime, "Page $url should load in less than 5 seconds");
    }

    /**
     * Test that pages have required security headers
     */
    #[DataProvider('successfulUrlsProvider')]
    public function testPagesHaveSecurityHeaders(string $url, array $result): void
    {
        $headers = array_change_key_case($result['headers'], CASE_LOWER);
        
        // Check for common security headers (not all are required, just testing approach)
        $securityHeaders = [
            'x-content-type-options',
            'x-frame-options',
            'content-security-policy',
        ];

        $hasSecurityHeader = false;
        foreach ($securityHeaders as $header) {
            if (isset($headers[$header])) {
                $hasSecurityHeader = true;
                break;
            }
        }

        // This is just an example - adapt based on your requirements
        if ($hasSecurityHeader) {
            $this->assertTrue(true, "Page $url has at least one security header");
        } else {
            $this->markTestIncomplete("Page $url has no security headers (this may be expected)");
        }
    }

    /**
     * Test that no critical errors occurred during crawling
     */
    #[DataProvider('allUrlsProvider')]
    public function testNoCriticalCrawlErrors(string $url, array $result): void
    {
        if (isset($result['error'])) {
            // Allow certain types of errors that might be expected
            $allowedErrors = [
                'Connection refused',
                'Connection timed out',
                'Name or service not known',
            ];

            $errorAllowed = false;
            foreach ($allowedErrors as $allowedError) {
                if (str_contains($result['error'], $allowedError)) {
                    $errorAllowed = true;
                    break;
                }
            }

            if (! $errorAllowed) {
                $this->fail("Critical crawl error for $url: {$result['error']}");
            } else {
                $this->markTestSkipped("Expected network error for $url: {$result['error']}");
            }
        } else {
            $this->assertTrue(true, "No crawl errors for $url");
        }
    }

    /**
     * Test that failed pages return expected error codes
     */
    #[DataProvider('failedUrlsProvider')]
    public function testFailedPagesReturnExpectedErrorCodes(string $url, array $result): void
    {
        if ($result['status_code'] === 0) {
            $this->markTestSkipped("Network error for $url");
        }

        $this->assertGreaterThanOrEqual(400, $result['status_code'], 
            "Failed page $url should return 4xx or 5xx status code");
    }

    /**
     * Test crawl summary statistics
     */
    public function testCrawlSummaryStatistics(): void
    {
        $results = $this->getCrawlResults();
        
        $this->assertNotEmpty($results, 'Should have crawled at least one page');
        
        $successful = array_filter($results, fn($r) => $r['status_code'] >= 200 && $r['status_code'] < 300);
        $failed = array_filter($results, fn($r) => $r['status_code'] >= 400);
        $errors = array_filter($results, fn($r) => isset($r['error']));
        
        $totalPages = count($results);
        $successfulPages = count($successful);
        $failedPages = count($failed);
        $errorPages = count($errors);
        
        // Log summary for debugging
        echo "\nCrawl Summary:\n";
        echo "Total pages: $totalPages\n";
        echo "Successful: $successfulPages\n";
        echo "Failed: $failedPages\n";
        echo "Errors: $errorPages\n";
        
        // Basic assertions about the crawl results
        $this->assertGreaterThan(0, $totalPages, 'Should have crawled at least one page');
        
        // At least 50% of pages should be successful (adjust as needed)
        if ($totalPages > 1) {
            $successRate = $successfulPages / $totalPages;
            $this->assertGreaterThanOrEqual(0.5, $successRate, 
                'At least 50% of crawled pages should be successful');
        }
    }
}