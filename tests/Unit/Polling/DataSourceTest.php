<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Interfaces\Discovery\MempoolsDiscovery;
use LibreNMS\Interfaces\Discovery\VminfoDiscovery;
use LibreNMS\Interfaces\Polling\VminfoPolling;
use LibreNMS\Polling\Method\DataSources\ProxmoxApiDataSource;
use LibreNMS\Polling\Method\DataSources\UnixAgentDataSource;
use LibreNMS\Polling\Method\PollingMethodRegistry;
use LibreNMS\Polling\Method\ProbeResult;
use LibreNMS\Polling\PerDeviceMethodResults;

test('methods with adata source', function () {
    $device = new Device(['hostname' => 'pve.example.com']);
    $registry = new PollingMethodRegistry;
    $expected = [
        PollingMethodType::ProxmoxApi->value => ProxmoxApiDataSource::class,
        PollingMethodType::UnixAgent->value => UnixAgentDataSource::class,
    ];

    foreach (PollingMethodType::cases() as $type) {
        $method = $registry->get($type);
        $source = $method->dataSource($method->config($device), ProbeResult::success());

        expect($source === null ? null : $source::class)->toBe($expected[$type->value] ?? null, $type->value);
    }
});

test('methods providing an interface', function () {
    $registry = new PollingMethodRegistry;

    expect($registry->providing(VminfoDiscovery::class))->toBe([PollingMethodType::ProxmoxApi]);
    expect($registry->providing(VminfoPolling::class))->toBe([PollingMethodType::ProxmoxApi]);
    expect($registry->providing(MempoolsDiscovery::class))->toBe([PollingMethodType::UnixAgent]);
});

test('data sources of available methods', function () {
    Http::fake([
        '*/api2/json/version' => Http::response(['data' => ['version' => '8.2.4']]),
        '*/certificates/info' => Http::response(['data' => [['filename' => 'pve-root-ca.pem', 'fingerprint' => 'AB:CD']]]),
    ]);
    $results = new PerDeviceMethodResults(device(enabled: true));

    $sources = $results->dataSources(VminfoDiscovery::class);

    expect($sources)->toHaveCount(1);
    expect($sources[0])->toBeInstanceOf(ProxmoxApiDataSource::class);
    expect($results->dataSources(VminfoDiscovery::class))->toBe($sources);
    // one source per method and run
    Http::assertSentCount(2);
    // checked once: the version and the cluster
});

test('no data source when the check fails or the method is disabled', function () {
    Http::fake(['*' => Http::response(null, 401)]);

    expect(new PerDeviceMethodResults(device(enabled: true))->dataSources(VminfoDiscovery::class))->toBe([]);
    expect(new PerDeviceMethodResults(device(enabled: false))->dataSources(VminfoDiscovery::class))->toBe([]);
});

test('failed check is logged once', function () {
    Http::fake(['*' => Http::response(null, 401)]);
    $log = Log::spy();
    $results = new PerDeviceMethodResults(device(enabled: true));

    $results->dataSources(VminfoDiscovery::class);
    $results->dataSources(VminfoPolling::class);

    $log->shouldHaveReceived('info')->once()->withArgs(fn (string $message): bool => str_contains($message, 'proxmox') && str_contains($message, '401'));
});

test('methods without adata source are not checked', function () {
    Http::fake();
    $device = new Device(['hostname' => 'pve.example.com']);
    $device->setRelation('pollingMethods', new Collection([
        new DevicePollingMethod(['method_type' => PollingMethodType::UnixAgent, 'enabled' => true]),
    ]));
    $results = new PerDeviceMethodResults($device);

    expect($results->dataSources(VminfoDiscovery::class))->toBe([]);
    expect($device->pollingMethod(PollingMethodType::UnixAgent)?->last_check_successful)->toBeNull();
    // not checked
});

function device(bool $enabled): Device
{
    config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
    // secret data is encrypted
    $proxmox = new DevicePollingMethod(['method_type' => PollingMethodType::ProxmoxApi, 'enabled' => $enabled]);
    $proxmox->setRelation('secret', new Secret([
        'secret_type' => SecretType::ProxmoxApi,
        'data' => ['token_id' => 'librenms@pve!monitoring', 'token_secret' => 'abc123'],
    ]));

    $device = new Device(['hostname' => 'pve.example.com']);
    $device->setRelation('pollingMethods', new Collection([
        new DevicePollingMethod(['method_type' => PollingMethodType::Icmp, 'enabled' => true]),
        $proxmox,
    ]));

    return $device;
}
