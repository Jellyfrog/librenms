<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Models\Mempool;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Modules\Mempools;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Method\Methods\UnixAgentPollingMethod;

uses(\LibreNMS\Tests\Traits\FakesPollingMethods::class);

test('agent only device discovers and polls recorded agent mempools', function () {
    $os = $this->makeOs([PollingMethodType::Icmp, PollingMethodType::UnixAgent]);
    $this->app->instance(UnixAgentPollingMethod::class, UnixAgentPollingMethod::recorded(base_path('tests/unix-agent/linux.txt')));
    $module = new Mempools;

    expect($module->shouldDiscover($os))->toBeTrue();
    $module->discover($os);

    // the recorded device has no swap, an empty swap mempool is rejected
    $mempools = Mempool::where('device_id', $os->getDeviceId())->get()->keyBy('mempool_class');
    expect($mempools->keys()->all())->toBe(['system', 'buffers', 'cached', 'available']);
    expect($mempools->pluck('polling_method')->unique()->values()->all())->toBe([PollingMethodType::UnixAgent]);
    expect($mempools['system']->mempool_total)->toEqual(65717444 * 1024);

    $datastore = Mockery::mock(DataStorageInterface::class);
    $datastore->shouldReceive('put')->times(4)->withArgs(fn ($device, $measurement) => $measurement == 'mempool');

    $os->getDevice()->unsetRelation('mempools');
    expect($module->shouldPoll($os))->toBeTrue();
    $module->poll($os, $datastore);
});

test('snmp is preferred and replaces agent mempools', function () {
    $os = $this->makeOs([PollingMethodType::UnixAgent, PollingMethodType::Snmp]);
    Mempool::factory()->create(['device_id' => $os->getDeviceId(), 'mempool_type' => 'unix-agent', 'mempool_index' => '1', 'polling_method' => PollingMethodType::UnixAgent]);
    $this->fakeMethod(SnmpPollingMethod::class, true);
    $os = $this->withSnmpMempools($os);
    $this->fakeMethod(UnixAgentPollingMethod::class, true, ['output' => $this->agentMemOutput(100, 50)]);

    (new Mempools)->discover($os);

    $mempools = Mempool::where('device_id', $os->getDeviceId())->get();
    expect($mempools->pluck('mempool_type')->all())->toBe(['hrstorage']);
    expect($mempools->first()?->polling_method)->toBe(PollingMethodType::Snmp);
    expect($this->wasChecked($os, PollingMethodType::UnixAgent))->toBeFalse();
});

test('does not fall back when snmp is down', function () {
    $os = $this->makeOs([PollingMethodType::Snmp, PollingMethodType::UnixAgent]);
    $this->fakeMethod(SnmpPollingMethod::class, false);
    $this->fakeMethod(UnixAgentPollingMethod::class, true, ['output' => $this->agentMemOutput(100, 50)]);

    expect((new Mempools)->shouldDiscover($os))->toBeFalse();
    expect($this->wasChecked($os, PollingMethodType::UnixAgent))->toBeFalse();
});

test('mempools are kept when no method has any', function () {
    $os = $this->makeOs([PollingMethodType::UnixAgent]);
    Mempool::factory()->create(['device_id' => $os->getDeviceId(), 'mempool_type' => 'unix-agent', 'mempool_index' => '1', 'polling_method' => PollingMethodType::UnixAgent]);
    $this->fakeMethod(UnixAgentPollingMethod::class, true);

    // incomplete output without the mem section
    $module = new Mempools;
    expect($module->shouldDiscover($os))->toBeTrue();
    $module->discover($os);

    expect(Mempool::where('device_id', $os->getDeviceId())->count())->toBe(1);
});

test('falls back when snmp has no mempools', function () {
    $os = $this->makeOs([PollingMethodType::Snmp, PollingMethodType::UnixAgent]);
    Mempool::factory()->create(['device_id' => $os->getDeviceId(), 'mempool_type' => 'unix-agent', 'mempool_index' => '1', 'polling_method' => PollingMethodType::UnixAgent]);
    $this->fakeMethod(SnmpPollingMethod::class, true);
    $os = $this->withSnmpMempools($os, null);
    $this->fakeMethod(UnixAgentPollingMethod::class, true, ['output' => $this->agentMemOutput(100, 50)]);

    (new Mempools)->discover($os);

    $mempool = Mempool::where('device_id', $os->getDeviceId())->sole();
    expect($mempool->mempool_type)->toBe('unix-agent');
    expect($mempool->polling_method)->toBe(PollingMethodType::UnixAgent);
    expect($mempool->mempool_perc)->toEqual(50);
});

test('does not poll mempools of disabled methods', function () {
    $os = $this->makeOs([PollingMethodType::UnixAgent]);
    Mempool::factory()->create(['device_id' => $os->getDeviceId(), 'mempool_type' => 'hrstorage', 'mempool_index' => '1', 'polling_method' => PollingMethodType::Snmp]);

    expect((new Mempools)->shouldPoll($os))->toBeFalse();
    expect($this->wasChecked($os, PollingMethodType::UnixAgent))->toBeFalse();
});

test('poll skips mempools of unavailable methods', function () {
    $os = $this->makeOs([PollingMethodType::Snmp, PollingMethodType::UnixAgent]);
    Mempool::factory()->create(['device_id' => $os->getDeviceId(), 'mempool_type' => 'hrstorage', 'mempool_index' => '1', 'polling_method' => PollingMethodType::Snmp]);
    Mempool::factory()->create(['device_id' => $os->getDeviceId(), 'mempool_type' => 'unix-agent', 'mempool_index' => '1', 'polling_method' => PollingMethodType::UnixAgent]);
    $this->fakeMethod(SnmpPollingMethod::class, false);
    $this->fakeMethod(UnixAgentPollingMethod::class, true, ['output' => $this->agentMemOutput(100, 40)]);

    $datastore = Mockery::mock(DataStorageInterface::class);
    $datastore->shouldReceive('put')->once()->withArgs(fn ($device, $measurement, $tags) => $tags['mempool_type'] == 'unix-agent');

    (new Mempools)->poll($os, $datastore);

    expect(Mempool::where('mempool_type', 'unix-agent')->value('mempool_perc'))->toEqual(60);
});

test('removing amethod deletes only its mempools', function () {
    $os = $this->makeOs([PollingMethodType::Snmp]);
    Mempool::factory()->create(['device_id' => $os->getDeviceId(), 'mempool_type' => 'hrstorage', 'mempool_index' => '1', 'polling_method' => PollingMethodType::Snmp]);
    Mempool::factory()->create(['device_id' => $os->getDeviceId(), 'mempool_type' => 'unix-agent', 'mempool_index' => '1', 'polling_method' => PollingMethodType::UnixAgent]);

    (new Mempools)->removeMethodData($os->getDevice(), PollingMethodType::UnixAgent);

    expect(Mempool::where('device_id', $os->getDeviceId())->pluck('mempool_type')->all())->toBe(['hrstorage']);
});