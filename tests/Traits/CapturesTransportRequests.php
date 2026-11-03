<?php

namespace LibreNMS\Tests\Traits;

use App\Models\Device;
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
     * Deliver the alert with HTTP faked and return the normalized requests it made.
     */
    protected function captureRequests(Transport $transport, array $alert): array
    {
        Http::fake();

        $this->assertTrue($transport->deliverAlert($alert));

        return Http::recorded()->map(fn (array $pair) => $this->normalizeRequest($pair[0]))->values()->all();
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

        $data = match (true) {
            $request->isJson() => json_decode($request->body(), true),
            $request->isForm() => $this->decodeForm($request->body()),
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
