<?php

namespace LibreNMS\Tests\Traits;

use App\Models\Device;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LibreNMS\Alert\AlertData;
use LibreNMS\Interfaces\Alert\Transport;

/**
 * Characterization testing for alert transports: deliver a fixed alert, capture what
 * would have gone over the wire, and compare it with a snapshot committed to the repo.
 *
 * Regenerate snapshots with UPDATE_TRANSPORT_SNAPSHOTS=1 and review the git diff.
 */
trait CapturesTransportRequests
{
    /**
     * Headers that are version dependent or just repeat the url or the size of the body.
     */
    private const IGNORED_HEADERS = ['Content-Length', 'Host', 'User-Agent'];

    /**
     * Test alert with every random or time based value pinned, so that snapshots are stable.
     */
    protected function fakeAlert(array $overrides = []): array
    {
        /** @var Device $device */
        $device = Device::factory()->make([
            'hostname' => 'test-host.example.com',
            'ip' => '192.0.2.1',
            'type' => 'network',
            'sysDescr' => 'Test device description',
            'status' => 1,
            'status_reason' => '',
        ]);

        return array_merge(AlertData::testData($device), ['timestamp' => '2026-01-02 03:04:05'], $overrides);
    }

    /**
     * Deliver the alert with HTTP faked and return the normalized requests it made,
     * through either Laravel's Http client or a Guzzle client from Http::guzzleClient().
     */
    protected function captureRequests(Transport $transport, array $alert): array
    {
        Http::fake();
        $history = $this->fakeGuzzle(new Response(200));

        $this->assertTrue($transport->deliverAlert($alert));

        $requests = Http::recorded()->map(fn (array $pair) => $pair[0])->values()->all();
        foreach ($history as $transaction) {
            $requests[] = new Request($transaction['request']);
        }

        return array_map($this->normalizeRequest(...), $requests);
    }

    /**
     * Make Http::guzzleClient() answer with the given response or exception, without network access.
     * The returned object fills with Guzzle's history entries: request, response, error and options.
     */
    protected function fakeGuzzle(mixed $response): \ArrayObject
    {
        $history = new \ArrayObject;
        $container = $history; // history() takes it by reference, which loses the type for static analysis
        $stack = HandlerStack::create(new MockHandler([$response]));
        $stack->push(Middleware::history($container));

        $this->app->bind(Client::class, fn ($app, array $params) => new Client(['handler' => $stack] + ($params['config'] ?? [])));

        return $history;
    }

    protected function assertMatchesTransportSnapshot(string $transport, string $scenario, array $actual): void
    {
        $path = __DIR__ . "/../Unit/Alert/Transports/snapshots/$transport.json";
        $snapshots = is_file($path) ? json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];

        if (getenv('UPDATE_TRANSPORT_SNAPSHOTS')) {
            $snapshots[$scenario] = $actual;
            ksort($snapshots);
            $json = json_encode($snapshots, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $this->assertNotFalse(file_put_contents($path, $json . PHP_EOL));

            return;
        }

        $this->assertArrayHasKey($scenario, $snapshots, "No snapshot for $transport/$scenario, generate it with UPDATE_TRANSPORT_SNAPSHOTS=1");
        $this->assertSame($snapshots[$scenario], $actual, "Request sent by $transport/$scenario differs from its snapshot");
    }

    /**
     * Field order means nothing on the wire, so sort it away. Values are decoded from the body
     * instead of read from Request::data(), which holds them before encoding (int vs string).
     */
    private function normalizeRequest(Request $request): array
    {
        $headers = array_diff_key($request->headers(), array_flip(self::IGNORED_HEADERS));
        ksort($headers);

        if (isset($headers['Content-Type'])) {
            $headers['Content-Type'] = preg_replace('/;\s*boundary=.*/', '', $headers['Content-Type']); // random per request
        }

        $data = match (true) {
            $request->isJson() => json_decode($request->body(), true),
            $request->isForm() => $this->decodeForm($request->body()),
            $request->isMultipart() => $this->decodeMultipart($request),
            default => null,
        };

        if (is_array($data)) {
            $this->sortKeys($data);
        }

        return [
            'method' => $request->method(),
            'url' => $request->url(),
            'headers' => $headers,
            'data' => $data,
            'body' => $data === null ? $request->body() : null,
        ];
    }

    /**
     * Not parse_str(), that rewrites field names and would hide a field like "expire " being sent.
     */
    private function decodeForm(string $body): array
    {
        $fields = [];
        foreach (array_filter(explode('&', $body)) as $pair) {
            [$name, $value] = explode('=', $pair, 2) + [1 => ''];
            $fields[urldecode($name)] = urldecode($value);
        }

        return $fields;
    }

    private function decodeMultipart(Request $request): array
    {
        preg_match('/boundary=(.+)$/', $request->header('Content-Type')[0], $boundary);

        $fields = [];
        foreach (explode('--' . $boundary[1], $request->body()) as $part) {
            if (preg_match('/name="([^"]*)".*?\r\n\r\n(.*)\r\n$/s', $part, $matches)) {
                $fields[$matches[1]] = $matches[2];
            }
        }

        return $fields;
    }

    private function sortKeys(array &$data): void
    {
        if (! array_is_list($data)) {
            ksort($data);
        }

        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->sortKeys($value);
            }
        }
    }
}
