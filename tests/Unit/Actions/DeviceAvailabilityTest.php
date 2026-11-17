<?php

uses(\LibreNMS\Polling\Method\Methods\PollingMethod::class);
use App\Actions\Device\CheckDeviceAvailability;
use \LibreNMS\Polling\Method\Methods\PollingMethod;
use \LibreNMS\Tests\Unit\Actions\FakePollingMethod;
use App\Actions\Device\SetDeviceAvailability;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use Illuminate\Support\Collection;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Exceptions\SecretDecryptionException;
use LibreNMS\Polling\Method\Config\PollingMethodConfig;
use LibreNMS\Polling\Method\Config\UnixAgentConfig;
use LibreNMS\Polling\Method\Definitions\UnixAgentDefinition;
use LibreNMS\Polling\Method\Methods\UnixAgentPollingMethod;
use LibreNMS\Polling\Method\ProbeResult;
use LibreNMS\Polling\PerDeviceMethodResults;

test('status reason lists failed methods that affect availability', function () {
    $snmp = method(PollingMethodType::Snmp, lastCheck: false);
    $icmp = method(PollingMethodType::Icmp, lastCheck: false);
    $ipmi = method(PollingMethodType::Ipmi, lastCheck: false, affectsAvailability: false);
    $device = device([$snmp, $icmp, $ipmi]);
    $setAvailability = new SetDeviceAvailability;

    expect($setAvailability->execute($device, commit: false))->toBeTrue();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('snmp,icmp');
    $device->syncOriginal();

    // saved
    // disabled methods don't count
    $snmp->enabled = false;
    expect($setAvailability->execute($device, commit: false))->toBeFalse();
    expect($device->status_reason)->toBe('icmp');

    $icmp->enabled = false;
    expect($setAvailability->execute($device, commit: false))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');
});

test('unchecked method is not failed', function () {
    $device = device([method(PollingMethodType::Snmp, lastCheck: null)]);

    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    (new SetDeviceAvailability)->execute($device, commit: false);
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');
});

test('method that can not be checked is failed and others are still checked', function () {
    $snmp = method(PollingMethodType::Snmp);
    $snmp->setRelation('secret', undecryptableSecret());
    $icmp = method(PollingMethodType::Icmp);
    $device = device([$snmp, $icmp]);
    mockFping(FpingResponse::artificialUp('192.0.2.1'));

    $status = app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device));

    expect($status)->toBeFalse();
    expect($snmp->last_check_successful)->toBeFalse();
    expect($snmp->last_check_message)->not->toBeNull();
    expect($icmp->last_check_successful)->toBeTrue();
    expect($device->status_reason)->toBe('snmp');
});

test('failed method that does not affect availability keeps device up', function () {
    $snmp = method(PollingMethodType::Snmp, affectsAvailability: false);
    $snmp->setRelation('secret', undecryptableSecret());
    $icmp = method(PollingMethodType::Icmp);
    $device = device([$snmp, $icmp]);
    mockFping(FpingResponse::artificialUp('192.0.2.1'));

    $methodResults = new PerDeviceMethodResults($device);
    expect(app(CheckDeviceAvailability::class)->execute($methodResults))->toBeTrue();
    expect($snmp->last_check_successful)->toBeTrue();
    // not checked yet, the first module that needs it checks it
    expect($device->status_reason)->toBe('');

    expect($methodResults->isAvailable(PollingMethodType::Snmp))->toBeFalse();
    expect($snmp->last_check_successful)->toBeFalse();
    expect($snmp->last_check_message)->toBe('Failed to decrypt credentials for snmp polling. Verify that APP_KEY matches the primary installation.');
    expect($device->status)->toBeTrue();
});

test('method is checked once for all modules', function () {
    $agent = new FakePollingMethod([true, false]);
    $this->app->instance(UnixAgentPollingMethod::class, $agent);
    $method = method(PollingMethodType::UnixAgent, lastCheck: null, affectsAvailability: false);
    $device = device([$method]);
    $methodResults = new PerDeviceMethodResults($device);

    app(CheckDeviceAvailability::class)->execute($methodResults);
    expect($agent->probes)->toBe(0);

    // does not affect availability
    expect($methodResults->isAvailable(PollingMethodType::UnixAgent))->toBeTrue();
    expect($methodResults->result(PollingMethodType::UnixAgent)?->stat('output'))->toBe('output');
    expect($agent->probes)->toBe(1);
    expect($method->last_check_successful)->toBeTrue();

    // a new poll checks again
    expect(new PerDeviceMethodResults($device)->isAvailable(PollingMethodType::UnixAgent))->toBeFalse();
    expect($agent->probes)->toBe(2);
});

test('disabled method is not checked', function () {
    $agent = new FakePollingMethod([true]);
    $this->app->instance(UnixAgentPollingMethod::class, $agent);
    $method = method(PollingMethodType::UnixAgent);
    $method->enabled = false;
    $methodResults = new PerDeviceMethodResults(device([$method]));

    app(CheckDeviceAvailability::class)->execute($methodResults);

    expect($methodResults->isAvailable(PollingMethodType::UnixAgent))->toBeFalse();
    expect($methodResults->result(PollingMethodType::UnixAgent))->toBeNull();
    expect($agent->probes)->toBe(0);
});

test('probe exception marks method failed', function () {
    $icmp = method(PollingMethodType::Icmp);
    $device = device([$icmp]);

    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andThrow(new \RuntimeException('fping exploded'));
    $this->app->instance(Fping::class, $fping);

    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($icmp->last_check_successful)->toBeFalse();
    expect($device->status_reason)->toBe('icmp');
});

test('device without polling methods keeps its status', function () {
    $device = device([]);
    $device->status = false;
    $device->status_reason = 'icmp';
    $device->syncOriginal();

    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('icmp');
    expect($device->isDirty())->toBeFalse();
});

test('unknown method is not checked and does not affect availability', function () {
    $unknown = new DevicePollingMethod([
        'method_type' => 'future-method',
        'enabled' => true,
        'affects_availability' => true,
        'last_check_successful' => false,
    ]);
    $icmp = method(PollingMethodType::Icmp);
    $device = device([$unknown, $icmp]);
    mockFping(FpingResponse::artificialUp('192.0.2.1'));
    $methodResults = new PerDeviceMethodResults($device);

    expect(app(CheckDeviceAvailability::class)->execute($methodResults))->toBeTrue();
    expect($device->status_reason)->toBe('');
    expect($methodResults->result(PollingMethodType::Unknown))->toBeNull();
    expect($unknown->last_check_successful)->toBeFalse();
});

test('device with only unknown methods keeps its status', function () {
    $device = device([new DevicePollingMethod([
        'method_type' => 'future-method',
        'enabled' => true,
        'affects_availability' => true,
    ])]);
    $device->status = false;
    $device->status_reason = 'icmp';
    $device->syncOriginal();

    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status_reason)->toBe('icmp');
    expect($device->isDirty())->toBeFalse();
});

test('secret of unknown type fails the method without reporting it', function () {
    $snmp = method(PollingMethodType::Snmp);
    $snmp->setRelation('secret', new Secret(['secret_type' => 'future-secret', 'data' => []]));
    $device = device([$snmp]);
    $this->app->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, Mockery::mock(\Illuminate\Contracts\Debug\ExceptionHandler::class)->shouldNotReceive('report')->getMock());

    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($snmp->last_check_successful)->toBeFalse();
    expect($snmp->last_check_message)->toBe('Unknown secret type for snmp polling, select another secret.');
});

/**
 * @param  DevicePollingMethod[]  $methods
 */
function device(array $methods): Device
{
    $device = new Device(['hostname' => '192.0.2.1', 'status' => true]);
    $device->syncOriginal();
    // as if loaded, so status changes are detected
    foreach ($methods as $method) {
        $method->setRelation('device', $device);
    }
    $device->setRelation('pollingMethods', new Collection($methods));

    return $device;
}

function method(PollingMethodType $type, ?bool $lastCheck = true, bool $affectsAvailability = true): DevicePollingMethod
{
    return new DevicePollingMethod([
        'method_type' => $type,
        'enabled' => true,
        'affects_availability' => $affectsAvailability,
        'last_check_successful' => $lastCheck,
    ]);
}

function undecryptableSecret(): Secret
{
    $secret = Mockery::mock(Secret::class)->makePartial();
    $secret->secret_type = SecretType::SnmpCommunity;
    $secret->shouldReceive('getAttribute')->with('data')->andThrow(SecretDecryptionException::failedToDecrypt('The payload is invalid.'));

    return $secret;
}

function mockFping(FpingResponse $response): void
{
    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andReturn($response);
    $this->app->instance(Fping::class, $fping);
}

/**
 * @extends PollingMethod<UnixAgentConfig>
 */
final class FakePollingMethod extends PollingMethod
{
    /**
     * @param  bool[]  $results
     */
    function __construct(private array $results)
    {
    }

    function probe(Device $device, PollingMethodConfig $config): ProbeResult
    {
        $this->probes++;

        return array_shift($this->results) ? ProbeResult::success(['output' => 'output']) : ProbeResult::failure(errorMessage: 'refused');
    }

    function definition(): UnixAgentDefinition
    {
        return new UnixAgentDefinition;
    }

    function defaultAffectsAvailability(): bool
    {
        return false;
    }

    function defaults(?Device $device = null): array
    {
        return [];
    }

    function config(Device $device, ?DevicePollingMethod $deviceMethod = null): UnixAgentConfig
    {
        return new UnixAgentConfig(port: 6556, timeout: 1);
    }
}
