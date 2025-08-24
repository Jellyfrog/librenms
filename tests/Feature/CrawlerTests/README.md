# LibreNMS Crawler Test Suite

This directory contains a comprehensive PHPUnit test suite for crawling and testing websites using the Spatie/Crawler package. The test suite is designed to be efficient, extensible, and production-ready.

## Features

- **Single Crawl Execution**: Crawling happens only once per test class, with results shared across all test methods
- **Data Providers**: Use PHPUnit data providers to run the same test against multiple crawled URLs
- **Configurable**: Extensive configuration options for crawl depth, delays, timeouts, and more
- **Error Handling**: Robust error handling with configurable allowed errors
- **Performance Testing**: Built-in performance metrics and response time testing
- **Extensible**: Easy to create new test classes for different websites or test scenarios

## Installation

The crawler test suite requires the `spatie/crawler` package, which should already be installed as a dev dependency:

```bash
composer require --dev spatie/crawler
```

## Usage

### Environment Variables

Set these environment variables to control crawler test behavior:

```bash
# Enable crawler tests (required)
export CRAWLER_TESTS=1

# Set base URL for testing (optional, defaults per test class)
export CRAWLER_BASE_URL=http://localhost:8000

# LibreNMS specific URL for web interface tests
export LIBRENMS_BASE_URL=http://localhost:8000

# Skip external URLs during crawling
export CRAWLER_SKIP_EXTERNAL=true

# Maximum crawl time in seconds
export CRAWLER_MAX_TIME=300

# Enable screenshots (requires additional setup)
export CRAWLER_SCREENSHOTS=false

# Set log level
export CRAWLER_LOG_LEVEL=info
```

### Running Tests

Run all crawler tests:

```bash
vendor/bin/phpunit --group=crawler
```

Run only the example website tests:

```bash
vendor/bin/phpunit tests/Feature/ExampleWebsiteCrawlerTest.php
```

Run LibreNMS web interface tests:

```bash
vendor/bin/phpunit tests/Feature/LibreNMSWebInterfaceCrawlerTest.php
```

### Creating Custom Test Classes

1. **Extend CrawlerTestCase**:

```php
<?php

namespace LibreNMS\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('crawler')]
class MyWebsiteCrawlerTest extends CrawlerTestCase
{
    protected function getBaseUrl(): string
    {
        return 'https://example.com';
    }
    
    // Add your test methods here...
}
```

2. **Configure Crawl Settings**:

```php
protected function getCrawlConfig(): array
{
    return array_merge(parent::getCrawlConfig(), [
        'max_depth' => 3,
        'delay_between_requests' => 500,
        'timeout' => 30,
        'concurrent_requests' => 2,
    ]);
}
```

3. **Create Custom Crawl Profile** (optional):

```php
protected function getCrawlProfile(): ?CrawlProfile
{
    return new MyCrawlProfile($this->getBaseUrl());
}
```

4. **Add Test Methods with Data Providers**:

```php
#[DataProvider('successfulUrlsProvider')]
public function testMyCustomValidation(string $url, array $result): void
{
    // Your test logic here
    $this->assertStringContainsString('expected-content', $result['body']);
}
```

## Data Providers

The base `CrawlerTestCase` provides three data providers:

- `successfulUrlsProvider()`: URLs with 2xx status codes
- `failedUrlsProvider()`: URLs with 4xx+ status codes  
- `allUrlsProvider()`: All crawled URLs regardless of status

Each provider returns arrays with: `[$url, $result]` where `$result` contains:

```php
[
    'status_code' => 200,
    'headers' => [...],
    'body' => '...',
    'found_on_url' => 'https://...',
    'response_time' => 1.234,
    'error' => null, // or error message if failed
]
```

## Configuration

### Default Configuration

The default crawl configuration is defined in `CrawlerTestCase`:

```php
protected array $crawlConfig = [
    'base_url' => 'http://localhost',
    'max_depth' => 3,
    'delay_between_requests' => 0,
    'timeout' => 30,
    'concurrent_requests' => 1,
    'ignore_robots' => true,
    'user_agent' => 'LibreNMS Test Crawler',
];
```

### Profile-Based Configuration

Use the configuration file at `config/crawler_config.php` for more advanced settings:

```php
// Load a predefined profile
$config = config('crawler_config.profiles.fast');
```

## Test Examples

### Basic Response Testing

```php
#[DataProvider('successfulUrlsProvider')]  
public function testPagesReturnSuccessfulResponse(string $url, array $result): void
{
    $this->assertGreaterThanOrEqual(200, $result['status_code']);
    $this->assertLessThan(300, $result['status_code']);
}
```

### Content Validation

```php
#[DataProvider('successfulUrlsProvider')]
public function testPagesContainRequiredContent(string $url, array $result): void
{
    $this->assertStringContainsString('<title>', $result['body']);
    $this->assertStringNotContainsString('Fatal Error', $result['body']);
}
```

### Performance Testing

```php
#[DataProvider('successfulUrlsProvider')]
public function testPagesLoadQuickly(string $url, array $result): void
{
    if ($result['response_time'] !== null) {
        $this->assertLessThan(5.0, (float) $result['response_time']);
    }
}
```

### API Testing

```php
#[DataProvider('allUrlsProvider')]
public function testAPIEndpointsReturnJson(string $url, array $result): void
{
    if (!str_contains($url, '/api/')) {
        $this->markTestSkipped('Not an API endpoint');
    }
    
    $contentType = $result['headers']['content-type'][0] ?? '';
    $this->assertStringContainsString('application/json', $contentType);
}
```

## Custom Crawl Profiles

Create custom crawl profiles to control which URLs are crawled:

```php
use Spatie\Crawler\CrawlProfiles\CrawlProfile;
use Psr\Http\Message\UriInterface;

class MyCrawlProfile extends CrawlProfile
{
    public function shouldCrawl(UriInterface $url): bool
    {
        $urlString = (string) $url;
        
        // Skip certain file types
        if (preg_match('/\.(jpg|png|pdf|zip)$/i', $urlString)) {
            return false;
        }
        
        // Skip admin areas
        if (str_contains($urlString, '/admin/')) {
            return false;
        }
        
        return true;
    }
}
```

## Error Handling

The test suite handles various types of errors gracefully:

- **Network Errors**: Connection timeouts, DNS failures, etc.
- **HTTP Errors**: 404, 500, etc. (configurable which are allowed)
- **Crawl Errors**: Invalid URLs, parsing errors, etc.

Configure allowed errors in your test class or the config file.

## Performance Considerations

- **Crawl Depth**: Limit `max_depth` to avoid crawling too many pages
- **Delays**: Use `delay_between_requests` to be respectful to servers
- **Concurrency**: Adjust `concurrent_requests` based on server capacity
- **Timeouts**: Set appropriate `timeout` values for your environment

## Troubleshooting

### Tests are Skipped

- Ensure `CRAWLER_TESTS=1` environment variable is set
- Check that the base URL is accessible
- Verify network connectivity

### Crawling Takes Too Long

- Reduce `max_depth`
- Increase `delay_between_requests`
- Reduce `concurrent_requests`
- Set a lower `timeout`

### Getting Network Errors

- Check if the target website is accessible
- Verify firewall settings
- Consider if the website blocks automated requests

### Memory Issues

- Reduce crawl depth
- Limit concurrent requests
- Clear results periodically for very large crawls

## Integration with CI/CD

Add crawler tests to your CI pipeline:

```yaml
# Example GitHub Actions
- name: Run Crawler Tests
  run: |
    export CRAWLER_TESTS=1
    export CRAWLER_BASE_URL=http://localhost:8000
    vendor/bin/phpunit --group=crawler
```

## Contributing

When contributing new crawler tests:

1. Follow the existing patterns and structure
2. Use appropriate PHPUnit attributes for grouping
3. Include proper error handling
4. Add documentation for complex test logic
5. Consider performance impact of your tests

## License

This crawler test suite is part of LibreNMS and is licensed under the GNU General Public License v3.0 or later.