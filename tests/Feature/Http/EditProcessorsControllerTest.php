<?php

use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Processor;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function editProcessorsAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function editProcessorsUser(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

test('admin can view processors page', function (): void {
    $device = Device::factory()->create();
    Processor::factory()->for($device)->create([
        'processor_descr' => 'CPU <b>0</b>',
        'processor_usage' => 42,
        'processor_perc_warn_custom' => 85,
    ]);

    $this->actingAs(editProcessorsAdmin())
        ->get(route('device.edit.processors', $device))
        ->assertOk()
        ->assertSee('CPU &lt;b&gt;0&lt;/b&gt;', false)
        ->assertDontSee('CPU <b>0</b>', false)
        ->assertSee('42%')
        ->assertSee('value="85"', false);
});

test('discovered threshold is shown as placeholder', function (): void {
    $device = Device::factory()->create();
    Processor::factory()->for($device)->create(['processor_perc_warn' => 80]);

    $this->actingAs(editProcessorsAdmin())
        ->get(route('device.edit.processors', $device))
        ->assertOk()
        ->assertSee('placeholder="80"', false)
        ->assertSee('value=""', false);
});

test('user cannot view processors page', function (): void {
    $device = Device::factory()->create();

    $this->actingAs(editProcessorsUser())
        ->get(route('device.edit.processors', $device))
        ->assertForbidden();
});

test('admin can update warn threshold', function (): void {
    $device = Device::factory()->create();
    $processor = Processor::factory()->for($device)->create(['processor_perc_warn' => 75]);

    $this->actingAs(editProcessorsAdmin())
        ->postJson(route('device.edit.processors.update', [$device, $processor]), ['processor_perc_warn_custom' => '90'])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $processor = $processor->fresh();
    expect($processor->processor_perc_warn_custom)->toBe(90);
    expect($processor->processor_perc_warn)->toBe(90);
});

test('admin can clear warn threshold', function (): void {
    LibrenmsConfig::set('processor_perc_warn', 70);
    $device = Device::factory()->create();
    $processor = Processor::factory()->for($device)->create(['processor_perc_warn_custom' => 90]);

    $this->actingAs(editProcessorsAdmin())
        ->postJson(route('device.edit.processors.update', [$device, $processor]), ['processor_perc_warn_custom' => ''])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $processor = $processor->fresh();
    expect($processor->processor_perc_warn_custom)->toBeNull();
    expect($processor->processor_perc_warn)->toBe(70);
});

test('rediscovery keeps user threshold', function (): void {
    $device = Device::factory()->create();
    $processor = Processor::factory()->for($device)->create(['processor_perc_warn_custom' => 90, 'processor_usage' => 10]);

    // discovery merges freshly discovered attributes, including a null or default threshold
    $processor->processor_perc_warn = 80;
    $processor->processor_usage = 20;
    $processor->save();

    $processor = $processor->fresh();
    expect($processor->processor_perc_warn)->toBe(90);
    expect($processor->processor_usage)->toEqual(20);
});

test('rediscovery updates discovered threshold', function (): void {
    LibrenmsConfig::set('processor_perc_warn', 70);
    $device = Device::factory()->create();
    $processor = Processor::factory()->for($device)->create(['processor_perc_warn' => 80]);

    // device reports a new threshold
    $processor->processor_perc_warn = 95;
    $processor->save();
    expect($processor->fresh()->processor_perc_warn)->toBe(95);

    // device no longer reports a threshold
    $processor->processor_perc_warn = null;
    $processor->save();
    expect($processor->fresh()->processor_perc_warn)->toBe(70);
});

/**
 * @return array<string, array{mixed}>
 */
dataset('invalidThresholds', function () {
    return [
        'non-numeric' => ['abc'],
        'negative' => [-1],
        'over 100' => [101],
    ];
});

test('invalid warn threshold is rejected', function (mixed $value): void {
    $device = Device::factory()->create();
    $processor = Processor::factory()->for($device)->create(['processor_perc_warn' => 75]);

    $this->actingAs(editProcessorsAdmin())
        ->postJson(route('device.edit.processors.update', [$device, $processor]), ['processor_perc_warn_custom' => $value])
        ->assertUnprocessable();

    expect($processor->fresh()->processor_perc_warn)->toBe(75);
})->with('invalidThresholds');

test('processor must belong to device', function (): void {
    $device = Device::factory()->create();
    $otherProcessor = Processor::factory()->create(['processor_perc_warn' => 75]);

    $this->actingAs(editProcessorsAdmin())
        ->postJson(route('device.edit.processors.update', [$device, $otherProcessor]), ['processor_perc_warn_custom' => 90])
        ->assertNotFound();

    expect($otherProcessor->fresh()->processor_perc_warn)->toBe(75);
});

test('user cannot update warn threshold', function (): void {
    $device = Device::factory()->create();
    $processor = Processor::factory()->for($device)->create(['processor_perc_warn' => 75]);

    $this->actingAs(editProcessorsUser())
        ->postJson(route('device.edit.processors.update', [$device, $processor]), ['processor_perc_warn_custom' => 90])
        ->assertForbidden();

    expect($processor->fresh()->processor_perc_warn)->toBe(75);
});