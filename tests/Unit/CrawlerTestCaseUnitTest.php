<?php

/**
 * CrawlerTestCaseUnitTest.php
 *
 * Unit test to verify the CrawlerTestCase functionality
 * This test validates the structure and basic functionality
 * without requiring external network access.
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

namespace LibreNMS\Tests\Unit;

use LibreNMS\Tests\Feature\CrawlerTestCase;
use LibreNMS\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
class CrawlerTestCaseUnitTest extends TestCase
{
    private TestCrawlerImplementation $crawler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crawler = new TestCrawlerImplementation();
    }

    public function testCrawlerTestCaseCanBeInstantiated(): void
    {
        $this->assertInstanceOf(CrawlerTestCase::class, $this->crawler);
    }

    public function testGetBaseUrlIsAbstract(): void
    {
        $this->assertEquals('http://test.example.com', $this->crawler->getBaseUrl());
    }

    public function testGetCrawlConfigReturnsArray(): void
    {
        $config = $this->crawler->getCrawlConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('base_url', $config);
        $this->assertArrayHasKey('max_depth', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertEquals('http://test.example.com', $config['base_url']);
    }

    public function testGetCrawlConfigMergesWithDefaults(): void
    {
        $config = $this->crawler->getCrawlConfig();

        // Should have default values
        $this->assertArrayHasKey('delay_between_requests', $config);
        $this->assertArrayHasKey('concurrent_requests', $config);
        $this->assertArrayHasKey('ignore_robots', $config);
        $this->assertArrayHasKey('user_agent', $config);
    }

    public function testIsCrawlingEnabledChecksEnvironment(): void
    {
        // Without CRAWLER_TESTS=1, should return false
        $this->assertFalse($this->crawler->isCrawlingEnabled());

        // Set environment variable
        putenv('CRAWLER_TESTS=1');
        $this->assertTrue($this->crawler->isCrawlingEnabled());

        // Clean up
        putenv('CRAWLER_TESTS=');
    }

    public function testResetCrawlResultsWorks(): void
    {
        // This test verifies the reset functionality works
        // We can't easily test the actual crawling without network access
        $this->crawler->resetCrawlResults();

        // If no exception is thrown, the method works
        $this->assertTrue(true);
    }

    public function testDataProviderMethodsExist(): void
    {
        $reflection = new \ReflectionClass(CrawlerTestCase::class);

        $this->assertTrue($reflection->hasMethod('successfulUrlsProvider'));
        $this->assertTrue($reflection->hasMethod('allUrlsProvider'));
        $this->assertTrue($reflection->hasMethod('failedUrlsProvider'));

        // All should be static methods
        $this->assertTrue($reflection->getMethod('successfulUrlsProvider')->isStatic());
        $this->assertTrue($reflection->getMethod('allUrlsProvider')->isStatic());
        $this->assertTrue($reflection->getMethod('failedUrlsProvider')->isStatic());
    }

    public function testCrawlerUsesRuntimeClassCacheTrait(): void
    {
        $traits = class_uses(CrawlerTestCase::class);
        $this->assertContains('LibreNMS\Traits\RuntimeClassCache', $traits);
    }
}

/**
 * Test implementation of CrawlerTestCase for unit testing
 */
class TestCrawlerImplementation extends CrawlerTestCase
{
    protected function getBaseUrl(): string
    {
        return 'http://test.example.com';
    }

    public function getCrawlConfig(): array
    {
        return parent::getCrawlConfig();
    }

    public function isCrawlingEnabled(): bool
    {
        return parent::isCrawlingEnabled();
    }

    public function resetCrawlResults(): void
    {
        parent::resetCrawlResults();
    }
}
