<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use Illuminate\Support\Facades\DB;
use LibreNMS\Enum\PollingMethodType;

test('check result is only written when it changes', function () {
    $this->travelTo('2026-10-05 10:00:00');
    $method = DevicePollingMethod::factory()->create([
        'device_id' => Device::factory()->create()->device_id,
        'method_type' => PollingMethodType::Icmp,
        'last_check_successful' => true,
    ]);
    expect($method->last_check_changed_at)->toEqual('2026-10-05 10:00:00');
    $method = $method->fresh();

    // loaded like a ping or poll does
    // the same result again is not written
    $this->travelTo('2026-10-05 10:05:00');
    DB::enableQueryLog();
    $method->last_check_successful = true;
    $method->last_check_message = null;
    $method->save();
    expect(DB::getQueryLog())->toBe([]);
    expect($method->fresh()->last_check_changed_at)->toEqual('2026-10-05 10:00:00');

    // a new result is written with the time it changed
    $this->travelTo('2026-10-05 10:10:00');
    $method->last_check_successful = false;
    $method->last_check_message = 'host : xmt/rcv/%loss = 3/0/100%';
    $method->save();
    expect($method->fresh()->last_check_changed_at)->toEqual('2026-10-05 10:10:00');

    // a message change alone does not move the time
    $this->travelTo('2026-10-05 10:15:00');
    $method->last_check_message = 'Timeout';
    $method->save();
    expect($method->fresh()->last_check_changed_at)->toEqual('2026-10-05 10:10:00');
});
