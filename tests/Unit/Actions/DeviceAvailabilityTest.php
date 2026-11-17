<?php

use App\Actions\Device\CheckDeviceAvailability;
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
use LibreNMS\Polling\Method\Methods\PollingMethod;
use LibreNMS\Polling\Method\Methods\UnixAgentPollingMethod;
use LibreNMS\Polling\Method\ProbeResult;
use LibreNMS\Polling\PerDeviceMethodResults;

uses(LibreNMS\Tests\TestCase::class);

test('status reason lists failed methods that affect availability', function (): void {
    $snmp = availabilityMethod(PollingMethodType::Snmp, lastCheck: false);
    $icmp = availabilityMethod(PollingMethodType::Icmp, lastCheck: false);
    $ipmi = availabilityMethod(PollingMethodType::Ipmi, lastCheck: false, affectsAvailability: false);
    $device = availabilityDevice([$snmp, $icmp, $ipmi]);
    $setAvailability = new SetDeviceAvailability;

    expect($setAvailability->execute($device, commit: false))->toBeTrue();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('snmp,icmp');
    $device->syncOriginal(); // saved

    // disabled methods don't count
    $snmp->enabled = false;
    expect($setAvailability->execute($device, commit: false))->toBeFalse();
    expect($device->status_reason)->toBe('icmp');

    $icmp->enabled = false;
    expect($setAvailability->execute($device, commit: false))->toBeTrue();
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');
});

test('unchecked method is not failed', function (): void {
    $device = availabilityDevice([availabilityMethod(PollingMethodType::Snmp, lastCheck: null)]);

    expect($device->polling()->failedAvailabilityChecks()->isEmpty())->toBeTrue();

    (new SetDeviceAvailability)->execute($device, commit: false);
    expect($device->status)->toBeTrue();
    expect($device->status_reason)->toBe('');
});

test('method that can not be checked is failed and others are still checked', function (): void {
    $snmp = availabilityMethod(PollingMethodType::Snmp);
    $snmp->setRelation('secret', undecryptableSecret());
    $icmp = availabilityMethod(PollingMethodType::Icmp);
    $device = availabilityDevice([$snmp, $icmp]);
    availabilityMockFping(FpingResponse::artificialUp('192.0.2.1'));

    $status = app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device));

    expect($status)->toBeFalse();
    expect($snmp->last_check_successful)->toBeFalse();
    expect($snmp->last_check_message)->not->toBeNull();
    expect($icmp->last_check_successful)->toBeTrue();
    expect($device->status_reason)->toBe('snmp');
});

test('failed method that does not affect availability keeps device up', function (): void {
    $snmp = availabilityMethod(PollingMethodType::Snmp, affectsAvailability: false);
    $snmp->setRelation('secret', undecryptableSecret());
    $icmp = availabilityMethod(PollingMethodType::Icmp);
    $device = availabilityDevice([$snmp, $icmp]);
    availabilityMockFping(FpingResponse::artificialUp('192.0.2.1'));

    $methodResults = new PerDeviceMethodResults($device);
    expect(app(CheckDeviceAvailability::class)->execute($methodResults))->toBeTrue();
    expect($snmp->last_check_successful)->toBeTrue(); // not checked yet, the first module that needs it checks it
    expect($device->status_reason)->toBe('');

    expect($methodResults->isAvailable(PollingMethodType::Snmp))->toBeFalse();
    expect($snmp->last_check_successful)->toBeFalse();
    expect($snmp->last_check_message)->toBe('Failed to decrypt credentials for snmp polling. Verify that APP_KEY matches the primary installation.');
    expect($device->status)->toBeTrue();
});

test('method is checked once for all modules', function (): void {
    $agent = new FakePollingMethod([true, false]);
    $this->app->instance(UnixAgentPollingMethod::class, $agent);
    $method = availabilityMethod(PollingMethodType::UnixAgent, lastCheck: null, affectsAvailability: false);
    $device = availabilityDevice([$method]);
    $methodResults = new PerDeviceMethodResults($device);

    app(CheckDeviceAvailability::class)->execute($methodResults);
    expect($agent->probes)->toBe(0); // does not affect availability

    expect($methodResults->isAvailable(PollingMethodType::UnixAgent))->toBeTrue();
    expect($methodResults->result(PollingMethodType::UnixAgent)?->stat('output'))->toBe('output');
    expect($agent->probes)->toBe(1);
    expect($method->last_check_successful)->toBeTrue();

    // a new poll checks again
    expect(new PerDeviceMethodResults($device)->isAvailable(PollingMethodType::UnixAgent))->toBeFalse();
    expect($agent->probes)->toBe(2);
});

test('disabled method is not checked', function (): void {
    $agent = new FakePollingMethod([true]);
    $this->app->instance(UnixAgentPollingMethod::class, $agent);
    $method = availabilityMethod(PollingMethodType::UnixAgent);
    $method->enabled = false;
    $methodResults = new PerDeviceMethodResults(availabilityDevice([$method]));

    app(CheckDeviceAvailability::class)->execute($methodResults);

    expect($methodResults->isAvailable(PollingMethodType::UnixAgent))->toBeFalse();
    expect($methodResults->result(PollingMethodType::UnixAgent))->toBeNull();
    expect($agent->probes)->toBe(0);
});

test('probe exception marks method failed', function (): void {
    $icmp = availabilityMethod(PollingMethodType::Icmp);
    $device = availabilityDevice([$icmp]);

    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andThrow(new \RuntimeException('fping exploded'));
    $this->app->instance(Fping::class, $fping);

    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($icmp->last_check_successful)->toBeFalse();
    expect($device->status_reason)->toBe('icmp');
});

test('device without polling methods keeps its status', function (): void {
    $device = availabilityDevice([]);
    $device->status = false;
    $device->status_reason = 'icmp';
    $device->syncOriginal();

    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($device->status)->toBeFalse();
    expect($device->status_reason)->toBe('icmp');
    expect($device->isDirty())->toBeFalse();
});

test('unknown method is not checked and does not affect availability', function (): void {
    $unknown = new DevicePollingMethod([
        'method_type' => 'future-method',
        'enabled' => true,
        'affects_availability' => true,
        'last_check_successful' => false,
    ]);
    $icmp = availabilityMethod(PollingMethodType::Icmp);
    $device = availabilityDevice([$unknown, $icmp]);
    availabilityMockFping(FpingResponse::artificialUp('192.0.2.1'));
    $methodResults = new PerDeviceMethodResults($device);

    expect(app(CheckDeviceAvailability::class)->execute($methodResults))->toBeTrue();
    expect($device->status_reason)->toBe('');
    expect($methodResults->result(PollingMethodType::Unknown))->toBeNull();
    expect($unknown->last_check_successful)->toBeFalse();
});

test('device with only unknown methods keeps its status', function (): void {
    $device = availabilityDevice([new DevicePollingMethod([
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

test('secret of unknown type fails the method without reporting it', function (): void {
    $snmp = availabilityMethod(PollingMethodType::Snmp);
    $snmp->setRelation('secret', new Secret(['secret_type' => 'future-secret', 'data' => []]));
    $device = availabilityDevice([$snmp]);
    $this->app->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, Mockery::mock(\Illuminate\Contracts\Debug\ExceptionHandler::class)->shouldNotReceive('report')->getMock());

    expect(app(CheckDeviceAvailability::class)->execute(new PerDeviceMethodResults($device)))->toBeFalse();
    expect($snmp->last_check_successful)->toBeFalse();
    expect($snmp->last_check_message)->toBe('Unknown secret type for snmp polling, select another secret.');
});

/**
 * @param  DevicePollingMethod[]  $methods
 */
function availabilityDevice(array $methods): Device
{
    $device = new Device(['hostname' => '192.0.2.1', 'status' => true]);
    $device->syncOriginal(); // as if loaded, so status changes are detected
    foreach ($methods as $method) {
        $method->setRelation('device', $device);
    }
    $device->setRelation('pollingMethods', new Collection($methods));

    return $device;
}

function availabilityMethod(PollingMethodType $type, ?bool $lastCheck = true, bool $affectsAvailability = true): DevicePollingMethod
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

function availabilityMockFping(FpingResponse $response): void
{
    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->andReturn($response);
    app()->instance(Fping::class, $fping);
}

/**
 * @extends PollingMethod<UnixAgentConfig>
 */
final class FakePollingMethod extends PollingMethod
{
    public int $probes = 0;

    /**
     * @param  bool[]  $results
     */
    public function __construct(private array $results)
    {
    }

    public function probe(Device $device, PollingMethodConfig $config): ProbeResult
    {
        $this->probes++;

        return array_shift($this->results) ? ProbeResult::success(['output' => 'output']) : ProbeResult::failure(errorMessage: 'refused');
    }

    public function definition(): UnixAgentDefinition
    {
        return new UnixAgentDefinition;
    }

    public function defaultAffectsAvailability(): bool
    {
        return false;
    }

    public function defaults(?Device $device = null): array
    {
        return [];
    }

    public function config(Device $device, ?DevicePollingMethod $deviceMethod = null): UnixAgentConfig
    {
        return new UnixAgentConfig(port: 6556, timeout: 1);
    }
}
