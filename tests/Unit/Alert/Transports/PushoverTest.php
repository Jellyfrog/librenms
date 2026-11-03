<?php

namespace LibreNMS\Tests\Unit\Alert\Transports;

use App\Facades\LibrenmsConfig;
use App\Models\AlertTransport;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use LibreNMS\Alert\Transport\Pushover;
use LibreNMS\Enum\AlertState;
use LibreNMS\Exceptions\AlertTransportDeliveryException;
use LibreNMS\Tests\TestCase;
use LibreNMS\Tests\Traits\CapturesTransportRequests;
use LibreNMS\Util\Version;
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
        $this->fakeGuzzle(new Response(400, [], '{"status":0,"errors":["invalid token"]}'));

        $this->expectException(AlertTransportDeliveryException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('invalid token');

        $this->transport('')->deliverAlert($this->fakeAlert());
    }

    public function testConnectionErrorThrows(): void
    {
        $this->fakeGuzzle(new ConnectException('Connection refused', new Request('POST', 'https://api.pushover.net')));

        $this->expectException(AlertTransportDeliveryException::class);
        $this->expectExceptionMessage('Connection refused');

        $this->transport('')->deliverAlert($this->fakeAlert());
    }

    public function testUsesLibrenmsHttpSettings(): void
    {
        LibrenmsConfig::set('https_proxy', 'http://proxy.example.com:3128');
        $history = $this->fakeGuzzle(new Response(200));

        $this->transport('')->deliverAlert($this->fakeAlert());

        $this->assertSame('LibreNMS/' . Version::VERSION, $history[0]['request']->getHeaderLine('User-Agent'));
        $this->assertSame('http://proxy.example.com:3128', $history[0]['options']['proxy']['https']);
        $this->assertSame(30, $history[0]['options']['timeout']);
        $this->assertSame(10, $history[0]['options']['connect_timeout']);
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
