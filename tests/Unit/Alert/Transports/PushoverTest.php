<?php

namespace LibreNMS\Tests\Unit\Alert\Transports;

use App\Models\AlertTransport;
use Illuminate\Support\Facades\Http;
use LibreNMS\Alert\Transport\Pushover;
use LibreNMS\Enum\AlertState;
use LibreNMS\Exceptions\AlertTransportDeliveryException;
use LibreNMS\Tests\TestCase;
use LibreNMS\Tests\Traits\CapturesTransportRequests;
use PHPUnit\Framework\Attributes\DataProvider;

final class PushoverTest extends TestCase
{
    use CapturesTransportRequests;

    private const SOUNDS = "sound_critical=siren\nsound_warning=bike\nsound_ok=magic";

    #[DataProvider('scenarios')]
    public function testSentRequest(string $options, array $alert): void
    {
        $requests = $this->captureRequests($this->transport($options), $this->fakeAlert($alert));

        $this->assertCount(1, $requests);
        $this->assertMatchesTransportSnapshot('pushover', (string) $this->dataName(), $requests);
    }

    public static function scenarios(): array
    {
        return [
            'critical' => ['', []],
            'warning' => ['', ['severity' => 'warning']],
            'ok severity' => ['', ['severity' => 'ok']],
            'recovered' => ['', ['state' => AlertState::RECOVERED]],
            'acknowledged' => ['', ['state' => AlertState::ACKNOWLEDGED]],
            'critical with sounds' => [self::SOUNDS, []],
            'warning with sounds' => [self::SOUNDS, ['severity' => 'warning']],
            'recovered with sounds' => [self::SOUNDS, ['state' => AlertState::RECOVERED]],
            'options override fields' => ["priority=2\r\nretry=60\r\n\r\nexpire = 300 \r\nnot an option\r\ntitle=Custom title", []],
            'special characters' => ['', [
                'title' => 'Räksmörgås & <b>bold</b>',
                'msg' => "Line one\nLine two &lt;tag&gt; 100% a+b=c",
            ]],
        ];
    }

    public function testMissingOptionsConfig(): void
    {
        $transport = new Pushover(new AlertTransport(['transport_config' => [
            'appkey' => 'test-app-key',
            'userkey' => 'test-user-key',
        ]]));

        $this->assertSame(
            $this->captureRequests($this->transport(''), $this->fakeAlert()),
            $this->captureRequests($transport, $this->fakeAlert()),
        );
    }

    public function testApiErrorThrows(): void
    {
        Http::fake(['api.pushover.net/*' => Http::response('{"status":0,"errors":["invalid token"]}', 400)]);

        $this->expectException(AlertTransportDeliveryException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('invalid token');

        $this->transport('')->deliverAlert($this->fakeAlert());
    }

    private function transport(string $options): Pushover
    {
        return new Pushover(new AlertTransport(['transport_config' => [
            'appkey' => 'test-app-key',
            'userkey' => 'test-user-key',
            'options' => $options,
        ]]));
    }
}
