<?php

/**
 * AlertOperationRunAlertsTest.php
 *
 * End-to-end tests that drive RunAlerts::runAlerts() against real database rows
 * (device, rule, operation, segments, transports, alert state) and assert which
 * transports get notified and that per-segment timer state is persisted.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 LibreNMS
 */

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Alert;
use App\Models\AlertFault;
use App\Models\AlertLog;
use App\Models\AlertOperation;
use App\Models\AlertRule;
use App\Models\AlertTransport;
use App\Models\Device;
use App\Models\Eventlog;
use App\Models\Processor;
use App\Models\Port;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LibreNMS\Alert\AlertRules;
use LibreNMS\Alert\AlertUtil;
use LibreNMS\Alert\RunAlerts;
use LibreNMS\Enum\AlertState;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('active alert issues due segment transport and respects timer', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ]);

    // Run twice in quick succession: the segment is due immediately (start_in = 0) on the
    // first run, but its 3600s repeat means the second run must not fire again.
    $captured = runAlertsCapturing(2);

    expect($captured)->toHaveCount(1, 'Segment should fire once, then be held off by its timer');
    expect(array_map(static fn ($t) => $t['transport_type'], $captured[0]))->toBe(['api'], 'The due segment\'s transport should be notified');

    // alerts.alerted is advanced to the active state
    $alerted = DB::table('alerts')
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->value('alerted');
    expect($alerted)->toEqual(AlertState::ACTIVE);

    // Per-segment timer state is persisted in alert_log details
    $segmentId = $context['segments'][0]['segment']->id;
    $details = latestAlertLogDetails($context['rule']->id);
    expect($details)->toHaveKey('op_seg');
    expect((int) ($details['op_seg'][(string) $segmentId]['fires'] ?? 0))->toBe(1);
});

test('only due segments are notified', function () {
    // Two independent segments: one due now, one not due for a long time.
    makeActiveAlert([
        ['type' => 'mail', 'start' => 0, 'dur' => 3600],
        ['type' => 'slack', 'start' => 99999, 'dur' => 3600],
    ]);

    $captured = runAlertsCapturing(1);

    expect($captured)->toHaveCount(1);
    expect(array_map(static fn ($t) => $t['transport_type'], $captured[0]))->toBe(['mail'], 'Only the segment whose timer is due should be notified');
});

test('multiple due segments notify union of transports', function () {
    // Two independent segments both due at the same time => union of their transports.
    makeActiveAlert([
        ['type' => 'mail', 'start' => 0, 'dur' => 3600],
        ['type' => 'slack', 'start' => 0, 'dur' => 3600],
    ]);

    $captured = runAlertsCapturing(1);

    expect($captured)->toHaveCount(1);
    expect(array_map(static fn ($t) => $t['transport_type'], $captured[0]))->toEqualCanonicalizing(['mail', 'slack'], 'Both due segments should be notified in a single cycle');
});

test('rule without operation is muted', function () {
    makeActiveAlert([], assignOperation: false);

    $captured = runAlertsCapturing(1);

    expect($captured)->toHaveCount(0, 'A rule with no operation assigned should not notify');
});

test('suppressed operation is muted', function () {
    makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ], suppressed: true);

    $captured = runAlertsCapturing(1);

    expect($captured)->toHaveCount(0, 'A suppressed operation should not notify');
});

test('acknowledged alert issues acknowledgement notification', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ]);

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });

    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toContain(AlertState::ACTIVE);

    AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->where('open', 1)
        ->update(['state' => AlertState::ACKNOWLEDGED]);
    new AlertRules($context['device']->device_id)->syncAlertState($context['rule']);

    $objs = [];
    $runAlerts->runAlerts();
    $runAlerts->runAcks();

    expect(issuedStates($objs))->toContain(AlertState::ACKNOWLEDGED, 'Acknowledging must send an acknowledgement notification');
    $alerted = DB::table('alerts')
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->value('alerted');
    expect($alerted)->toEqual(AlertState::ACKNOWLEDGED);
});

test('acknowledged faults send while aggregate still active', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ]);

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });

    $runAlerts->runAlerts();

    AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->where('open', 1)
        ->update(['state' => AlertState::ACKNOWLEDGED]);

    $objs = [];
    $runAlerts->runAlerts();

    expect(issuedStates($objs))->toContain(AlertState::ACKNOWLEDGED, 'All-ack remaining faults must notify even if the alerts row is still ACTIVE');
});

test('per entity acknowledgement issues its own notification', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ], seedAlert: false);
    $context['rule']->update([
        'notify_per_entity' => true,
        'max_entities' => 10,
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);

    Processor::factory()->for($context['device'])->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);
    Processor::factory()->for($context['device'])->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 96,
    ]);

    new AlertRules($context['device'])->run();

    AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->update(['alerted' => AlertState::ACTIVE]);

    $acked = AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->where('open', 1)
        ->orderBy('id')
        ->firstOrFail();
    $acked->state = AlertState::ACKNOWLEDGED;
    $acked->save();

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();

    expect(issuedStates($objs))->toContain(AlertState::ACKNOWLEDGED, 'Per-entity acknowledge must send an acknowledgement for the acked fault');
    expect((int) $acked->fresh()->alerted)->toBe(AlertState::ACKNOWLEDGED);
    expect(issuedStates($objs))->toContain(AlertState::ACTIVE);
});

/** @return iterable<string, array{bool, bool}> */
dataset('acknowledgedPortRecoveries', function () {
    foreach ([false, true] as $untilClear) {
        foreach ([false, true] as $recoverA) {
            yield ($untilClear ? 'until clear' : 'until change') . ($recoverA ? ' A recovers' : ' B recovers') => [$untilClear, $recoverA];
        }
    }
});

test('per entity acknowledgement survives other port changes', function (bool $untilClear, bool $recoverA) {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ], seedAlert: false);
    $device = $context['device'];
    $rule = $context['rule'];
    $rule->update([
        'notify_per_entity' => true,
        'max_entities' => 2,
        'query' => 'SELECT * FROM ports WHERE device_id = ? AND ifOperStatus = "down"',
    ]);
    $a = Port::factory()->for($device)->create(['ifOperStatus' => 'down']);
    $b = Port::factory()->for($device)->create(['ifOperStatus' => 'up']);
    Role::findOrCreate('admin');
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $rules = new AlertRules($device);
    $rules->run();
    $runAlerts->runAlerts();
    expect($objs)->toHaveCount(1);
    $faultA = AlertFault::query()->where('rule_id', $rule->id)->where('entity_id', $a->port_id)->firstOrFail();
    $this->postJson(route('fault.ack', $faultA), [
        'state' => AlertState::ACTIVE,
        'ack_until_clear' => $untilClear ? '1' : '0',
        'ack_msg' => 'Investigating port A',
    ])->assertOk()->assertJson(['status' => 'ok']);
    $runAlerts->runAcks();
    $marker = (int) $faultA->fresh()->alerted;
    expect($marker)->toBe(AlertState::ACKNOWLEDGED);
    $note = $faultA->fresh()->note;

    $b->update(['ifOperStatus' => 'down']);
    $rules->run();
    $faultB = AlertFault::query()->where('rule_id', $rule->id)->where('entity_id', $b->port_id)->firstOrFail();
    expect((int) $faultA->fresh()->state)->toBe(AlertState::ACKNOWLEDGED);
    expect((int) $faultA->fresh()->alerted)->toBe($marker);
    expect($faultA->fresh()->note)->toBe($note);
    expect((int) $faultB->state)->toBe(AlertState::ACTIVE);
    expect((int) $faultB->alerted)->toBe(0);
    $objs = [];
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toBe([AlertState::ACTIVE]);
    expect(issuedPortIds($objs))->toBe([$b->port_id]);
    expect((int) $faultB->fresh()->alerted)->toBe(AlertState::WORSE);
    $runAlerts->runAlerts();
    expect($objs)->toHaveCount(1, 'Neither the acknowledged fault nor the new problem should be sent twice');

    // Acknowledging B must leave A's acknowledgement options and note alone.
    $this->postJson(route('fault.ack', $faultB), [
        'state' => AlertState::ACTIVE,
        'ack_until_clear' => $untilClear ? '0' : '1',
    ])->assertOk()->assertJson(['status' => 'ok']);
    $runAlerts->runAcks();
    expect($faultA->fresh()->info['until_clear'])->toBe($untilClear);
    expect($faultA->fresh()->note)->toBe($note);

    $recoveringPort = $recoverA ? $a : $b;
    $recoveringFault = $recoverA ? $faultA : $faultB;
    $remainingFault = $recoverA ? $faultB : $faultA;
    $recoveringPort->update(['ifOperStatus' => 'up']);
    $rules->run();
    expect((int) $recoveringFault->fresh()->state)->toBe(AlertState::RECOVERED);
    expect((int) $remainingFault->fresh()->state)->toBe(AlertState::ACKNOWLEDGED);
    $objs = [];
    $runAlerts->runAcks();
    expect(issuedStates($objs))->toBe([AlertState::RECOVERED]);
    expect((int) $recoveringFault->fresh()->open)->toBe(0);

    // A new incident for the recovered port must alert again and retain the old history.
    $recoveringPort->update(['ifOperStatus' => 'down']);
    $rules->run();
    $objs = [];
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toBe([AlertState::ACTIVE]);
    expect(issuedPortIds($objs))->toBe([$recoveringPort->port_id]);
    expect((int) $remainingFault->fresh()->state)->toBe(AlertState::ACKNOWLEDGED);
    expect(AlertLog::query()
        ->where('fault_id', $recoveringFault->id)->orderBy('id')->toBase()->pluck('state')->all())->toBe([AlertState::ACTIVE, AlertState::RECOVERED]);
    expect(AlertFault::query()->where('rule_id', $rule->id)
        ->where('entity_id', $recoveringPort->port_id)->count())->toBe(2);
})->with('acknowledgedPortRecoveries');

test('crossing entity limit retains grouped change notifications', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ], seedAlert: false);
    $device = $context['device'];
    $rule = $context['rule'];
    $rule->update([
        'notify_per_entity' => true,
        'max_entities' => 1,
        'query' => 'SELECT * FROM ports WHERE device_id = ? AND ifOperStatus = "down"',
    ]);
    $a = Port::factory()->for($device)->create(['ifOperStatus' => 'down']);
    $b = Port::factory()->for($device)->create(['ifOperStatus' => 'up']);
    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $rules = new AlertRules($device);
    $rules->run();
    $runAlerts->runAlerts();
    $faultA = AlertFault::query()->where('rule_id', $rule->id)->firstOrFail();
    $faultA->update(['state' => AlertState::ACKNOWLEDGED, 'info' => ['until_clear' => false]]);
    $rules->syncAlertState($rule);
    $runAlerts->runAcks();

    $b->update(['ifOperStatus' => 'down']);
    $rules->run();
    $objs = [];
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toBe([AlertState::WORSE]);
    expect(issuedPortIds($objs))->toEqualCanonicalizing([$a->port_id, $b->port_id]);
    expect((int) $faultA->fresh()->state)->toBe(AlertState::ACTIVE);

    // A grouped acknowledgement expires on a set change, even when the new count is per-entity.
    AlertFault::query()->where('rule_id', $rule->id)->update([
        'state' => AlertState::ACKNOWLEDGED,
        'info' => ['until_clear' => false],
    ]);
    $rules->syncAlertState($rule);
    $runAlerts->runAcks();
    $b->update(['ifOperStatus' => 'up']);
    $rules->run();
    $this->assertDatabaseHas('alerts', ['rule_id' => $rule->id, 'state' => AlertState::BETTER]);
    $objs = [];
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toBe([AlertState::ACTIVE, AlertState::RECOVERED]);
    expect(issuedPortIds($objs))->toBe([$a->port_id, $b->port_id]);
    expect((int) $faultA->fresh()->state)->toBe(AlertState::ACTIVE);
    expect(AlertFault::query()->where('rule_id', $rule->id)->where('open', 1)->count())->toBe(1);
});

test('grouped partial recovery does not send recovery', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ], seedAlert: false);
    $context['rule']->update([
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);

    $keepProcessor = Processor::factory()->for($context['device'])->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);
    $recoverProcessor = Processor::factory()->for($context['device'])->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 96,
    ]);

    $alertRules = new AlertRules($context['device']);
    $alertRules->run();

    AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->update(['alerted' => AlertState::ACTIVE]);

    $recoverProcessor->processor_usage = 10;
    $recoverProcessor->save();
    $alertRules->run();

    $recovered = AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->where('state', AlertState::RECOVERED)
        ->firstOrFail();
    $recoveredLog = AlertLog::query()
        ->where('fault_id', $recovered->id)
        ->where('state', AlertState::RECOVERED)
        ->orderByDesc('id')
        ->firstOrFail();
    $betterLog = AlertLog::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->whereNull('fault_id')
        ->where('state', AlertState::BETTER)
        ->orderByDesc('id')
        ->firstOrFail();

    expect($betterLog->id)->toBeGreaterThan($recoveredLog->id, 'syncAlertState must write the rule-level BETTER log after the fault RECOVERED log');

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();

    expect(array_column($objs, 'state'))->toContain(AlertState::BETTER);
    expect(issuedStates($objs))->not->toContain(AlertState::RECOVERED, 'Grouped rules must not send a recovery until every entity has recovered');
    expect((int) $recovered->fresh()->open)->toBe(0, 'The recovered fault must still be closed');
    $keep = AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->where('state', AlertState::ACTIVE)
        ->first();
    expect($keep)->not->toBeNull();
    expect((int) $keep->entity_id)->toBe($keepProcessor->processor_id);
    expect((int) $keep->alerted)->toBe(AlertState::BETTER, 'Remaining faults must be marked alerted when the problem notification is sent');
});

test('better worse repeats as active until count changes again', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ], seedAlert: false);
    $context['rule']->update([
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);

    $keep = Processor::factory()->for($context['device'])->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);
    $recover = Processor::factory()->for($context['device'])->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 96,
    ]);

    $alertRules = new AlertRules($context['device']);
    $alertRules->run();

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toContain(AlertState::ACTIVE);

    $recover->processor_usage = 10;
    $recover->save();
    $alertRules->run();

    $objs = [];
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toBe([AlertState::BETTER]);
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::BETTER,
        'alerted' => AlertState::BETTER,
    ]);

    expireProblemSegmentTimers($context['rule']->id);

    $objs = [];
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toBe([AlertState::ACTIVE], 'Repeats after BETTER must be a normal ACTIVE alert');
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::BETTER,
        'alerted' => AlertState::BETTER,
    ]);

    $recover->processor_usage = 96;
    $recover->save();
    $alertRules->run();

    $objs = [];
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toContain(AlertState::WORSE, 'A further count change must send WORSE again');
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::WORSE,
    ]);
});

test('per entity partial recovery issues recovery and closes recovered fault', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ], seedAlert: false);
    $context['rule']->update([
        'notify_per_entity' => true,
        'max_entities' => 10,
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);

    $keepProcessor = Processor::factory()->for($context['device'])->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);
    $recoverProcessor = Processor::factory()->for($context['device'])->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 96,
    ]);

    $alertRules = new AlertRules($context['device']);
    $alertRules->run();

    AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->update(['alerted' => AlertState::ACTIVE]);

    $recoverProcessor->processor_usage = 10;
    $recoverProcessor->save();
    $alertRules->run();

    $recovered = AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->where('state', AlertState::RECOVERED)
        ->firstOrFail();
    $recoveredActiveLog = AlertLog::query()
        ->where('fault_id', $recovered->id)
        ->where('state', AlertState::ACTIVE)
        ->orderBy('id')
        ->firstOrFail();
    $recoveredLog = AlertLog::query()
        ->where('fault_id', $recovered->id)
        ->where('state', AlertState::RECOVERED)
        ->orderByDesc('id')
        ->firstOrFail();

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();

    $recovery = collect($objs)->first(fn ($obj) => (int) $obj['state'] === AlertState::RECOVERED);
    expect($recovery)->not->toBeNull('Per-entity rules send a recovery as each entity clears');
    expect($recovery['uid'])->toBe($recoveredLog->id, 'Recovery must use the fault RECOVERED log, not a later BETTER log');
    expect($recovery['id'])->toBe($recoveredActiveLog->id, 'elapsed/previousLog must be the recovered fault\'s prior incident');
    expect((int) $recovered->fresh()->open)->toBe(0, 'The recovered fault must be closed after delivery');
    $keep = AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->where('state', AlertState::ACTIVE)
        ->first();
    expect($keep)->not->toBeNull();
    expect((int) $keep->entity_id)->toBe($keepProcessor->processor_id);
});

test('pending recovery is skipped when problem was never notified', function () {
    // First problem segment is delayed; the entity recovers before that timer is due.
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 300, 'dur' => 3600],
    ]);

    $recovered = AlertFault::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'entity_key' => 'entity|recovered',
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => 0,
        'details' => ['rule' => [['entity' => 'recovered']], 'contacts' => []],
    ]);

    AlertLog::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'fault_id' => $recovered->id,
        'state' => AlertState::ACTIVE,
        'details' => ['rule' => [['entity' => 'recovered']], 'contacts' => []],
        'time_logged' => now()->subMinutes(2),
    ]);
    AlertLog::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'fault_id' => $recovered->id,
        'state' => AlertState::RECOVERED,
        'details' => ['rule' => [['entity' => 'recovered']], 'contacts' => []],
    ]);
    AlertLog::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'fault_id' => null,
        'state' => AlertState::BETTER,
        'details' => ['rule' => [['entity' => 'keep']], 'contacts' => []],
    ]);

    Alert::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->firstOrFail()
        ->update([
            'state' => AlertState::BETTER,
            'alerted' => AlertState::CLEAR,
            'open' => 1,
            'info' => ['open_fault_count' => 1],
        ]);

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();

    expect(array_filter($objs, fn ($obj) => (int) $obj['state'] === AlertState::RECOVERED))->toBeEmpty('A recovered entity must not get a recovery notification when its problem was never sent');
    expect((int) $recovered->fresh()->open)->toBe(0, 'The unnotified recovered fault must still be closed');
});

test('orphaned operation id does not send recovery', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ]);
    $context['rule']->update(['alert_operation_id' => 2147483647]);

    $recovered = AlertFault::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'entity_key' => 'entity|recovered',
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'details' => ['rule' => [['entity' => 'recovered']], 'contacts' => []],
    ]);
    AlertLog::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'fault_id' => $recovered->id,
        'state' => AlertState::RECOVERED,
        'details' => ['rule' => [['entity' => 'recovered']], 'contacts' => []],
    ]);
    Alert::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->update([
            'state' => AlertState::BETTER,
            'alerted' => AlertState::CLEAR,
            'open' => 1,
            'info' => ['open_fault_count' => 1],
        ]);

    expect(AlertUtil::operationNotificationsSuppressed($context['rule']->id))->toBeTrue();
    expect(AlertUtil::shouldSendRecovery($context['rule']->id, $context['device']->device_id))->toBeFalse();

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();

    expect($objs)->toBeEmpty('An orphaned alert_operation_id must be treated as suppressed');
    expect((int) $recovered->fresh()->open)->toBe(0);
});

test('full recovery does not resend after interval continue', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ]);
    $context['rule']->update(['extra' => ['interval' => 3600]]);

    $fault = AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->firstOrFail();
    $fault->update([
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
    ]);

    $recoveredLog = AlertLog::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'fault_id' => $fault->id,
        'state' => AlertState::RECOVERED,
        'details' => ['rule' => [], 'contacts' => [], 'interval' => time()],
    ]);

    Alert::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->firstOrFail()
        ->update([
            'state' => AlertState::RECOVERED,
            'alerted' => AlertState::ACTIVE,
            'open' => 1,
            'info' => ['open_fault_count' => 0],
        ]);

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();

    expect(array_filter($objs, fn ($obj) => (int) $obj['state'] === AlertState::RECOVERED))->toBeEmpty('Interval gate must hold the recovery in the main loop, not a second dispatcher');
    expect((int) $fault->fresh()->open)->toBe(1);
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
    ]);

    $details = $recoveredLog->fresh()->details;
    $details['interval'] = time() - 10000;
    $recoveredLog->details = $details;
    $recoveredLog->save();

    $runAlerts->runAlerts();

    expect(array_filter($objs, fn ($obj) => (int) $obj['state'] === AlertState::RECOVERED))->toHaveCount(1);
    expect((int) $fault->fresh()->open)->toBe(0);
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::RECOVERED,
        'open' => 0,
        'alerted' => AlertState::RECOVERED,
    ]);
});

test('recovered alert with zeroed alerted still sends when fault was notified', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ]);

    $fault = AlertFault::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->firstOrFail();
    $fault->update([
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
    ]);
    AlertLog::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'fault_id' => $fault->id,
        'state' => AlertState::RECOVERED,
        'details' => ['rule' => [], 'contacts' => []],
    ]);
    Alert::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->update([
            'state' => AlertState::RECOVERED,
            'alerted' => 0,
            'open' => 1,
            'info' => ['open_fault_count' => 0],
        ]);

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();

    expect(issuedStates($objs))->toContain(AlertState::RECOVERED, 'alerted == state (both 0) must not drop recovery when the problem was notified');
    expect((int) $fault->fresh()->open)->toBe(0);
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::RECOVERED,
        'open' => 0,
    ]);
});

test('full recovery after better still sends recovery', function () {
    // Problem fires, then entities recover across polls. BETTER zeros alerts.alerted;
    // the last entity recovering must still dispatch the recovery transport (0 == 0
    // must not be treated as "already issued").
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ], seedAlert: false);
    $context['rule']->update([
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);

    $keep = Processor::factory()->for($context['device'])->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);
    $recover = Processor::factory()->for($context['device'])->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 96,
    ]);

    $alertRules = new AlertRules($context['device']);
    $alertRules->run();

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toContain(AlertState::ACTIVE);

    $recover->processor_usage = 10;
    $recover->save();
    $alertRules->run();
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::BETTER,
        'alerted' => 0,
    ]);

    $objs = [];
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->not->toContain(AlertState::RECOVERED, 'A repeat is not due yet, so BETTER must not send recovery for the remaining problem');

    $keep->processor_usage = 10;
    $keep->save();
    $alertRules->run();
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::RECOVERED,
        'open' => 1,
    ]);
    $this->assertNotSame(
        0,
        (int) Alert::query()
            ->where('rule_id', $context['rule']->id)
            ->where('device_id', $context['device']->device_id)
            ->value('alerted'),
        'alerted must not stay at 0 across a notified full recovery'
    );

    $objs = [];
    $runAlerts->runAlerts();
    expect(issuedStates($objs))->toContain(AlertState::RECOVERED, 'Full recovery after a BETTER cycle must still send the recovery notification');
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::RECOVERED,
        'open' => 0,
    ]);
});

test('parent down suppresses alert without advancing segment timers', function () {
    // Parent is down; child has an active, unnotified alert.
    $parent = Device::factory()->create(['status' => 0, 'ignore' => 0, 'disabled' => 0]);
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ]);

    DB::table('device_relationships')->insert([
        'parent_device_id' => $parent->device_id,
        'child_device_id' => $context['device']->device_id,
    ]);

    // Run several times while parent is down.
    // The alert must be suppressed and segment timer state must NOT advance.
    $captured = runAlertsCapturing(3);
    expect($captured)->toHaveCount(0, 'No alert should fire while parent is down');
    expect(Eventlog::query()
        ->where('device_id', $context['device']->device_id)
        ->where('message', 'Skipped alerts because all parent devices are down')
        ->count())->toBe(1, 'Parent-down skip must be eventlogged once, not every cycle');

    $segmentId = $context['segments'][0]['segment']->id;
    $details = latestAlertLogDetails($context['rule']->id);
    $fires = (int) ($details['op_seg'][(string) $segmentId]['fires'] ?? 0);
    expect($fires)->toBe(0, 'Segment timer must not advance while parent is suppressing the alert');

    // Bring parent back up.  The child alert must fire immediately on the next cycle.
    DB::table('devices')
        ->where('device_id', $parent->device_id)
        ->update(['status' => 1]);

    $captured = runAlertsCapturing(1);
    expect($captured)->toHaveCount(1, 'Alert must fire as soon as parent recovers with child still down');
    expect(array_map(static fn ($t) => $t['transport_type'], $captured[0]))->toBe(['api'], 'The correct transport should be notified on the first unsuppressed cycle');

    $alerted = DB::table('alerts')
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->value('alerted');
    expect($alerted)->toEqual(AlertState::ACTIVE, 'alerts.alerted must be advanced after the notification fires');
});

test('parent down closes recoveries without retry', function () {
    $parent = Device::factory()->create(['status' => 0, 'ignore' => 0, 'disabled' => 0]);
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 0, 'dur' => 3600],
    ]);

    DB::table('device_relationships')->insert([
        'parent_device_id' => $parent->device_id,
        'child_device_id' => $context['device']->device_id,
    ]);

    $recovered = AlertFault::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'entity_key' => 'entity|recovered',
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'details' => ['rule' => [['entity' => 'recovered']], 'contacts' => []],
    ]);
    AlertLog::create([
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'fault_id' => $recovered->id,
        'state' => AlertState::RECOVERED,
        'details' => ['rule' => [['entity' => 'recovered']], 'contacts' => []],
    ]);

    Alert::query()
        ->where('rule_id', $context['rule']->id)
        ->where('device_id', $context['device']->device_id)
        ->update([
            'state' => AlertState::BETTER,
            'alerted' => AlertState::CLEAR,
            'open' => 1,
            'info' => ['open_fault_count' => 1],
        ]);

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();
    $runAlerts->runAlerts();

    expect((int) $recovered->fresh()->open)->toBe(0, 'Parent-down recoveries must be closed, not retried');
    expect(collect($objs)->where('state', AlertState::RECOVERED))->toHaveCount(0, 'No recovery notification while the parent is down');

    DB::table('devices')
        ->where('device_id', $parent->device_id)
        ->update(['status' => 1]);

    $runAlerts->runAlerts();

    expect(collect($objs)->where('state', AlertState::RECOVERED))->toHaveCount(0, 'Closed parent-down recoveries must not burst when the parent returns');
});

test('grouped rule sends recovery when fault recovers before dispatcher reaches alert', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 300, 'dur' => 3600],
    ]);

    $objs = runRecoveryRace($context['rule'], $context['device']);

    expect(array_filter(issuedStates($objs), fn ($state) => $state === AlertState::RECOVERED))->toHaveCount(1, 'A grouped rule must send exactly one recovery when the fault recovers after the alerts snapshot was loaded');
    $this->assertDatabaseHas('alerts', [
        'rule_id' => $context['rule']->id,
        'device_id' => $context['device']->device_id,
        'state' => AlertState::RECOVERED,
        'open' => 0,
    ]);
});

test('per entity rule recovery unchanged when fault recovers before dispatcher', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 300, 'dur' => 3600],
    ]);
    $context['rule']->update([
        'notify_per_entity' => true,
        'max_entities' => 10,
    ]);

    $objs = runRecoveryRace($context['rule'], $context['device']);

    expect(array_filter(issuedStates($objs), fn ($state) => $state === AlertState::RECOVERED))->toHaveCount(1, 'A per-entity rule must still send exactly one recovery');
});

test('grouped race recovery not sent for ignored device', function () {
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 300, 'dur' => 3600],
    ]);
    $context['device']->update(['ignore' => 1]);

    $objs = runRecoveryRace($context['rule'], $context['device']);

    expect(issuedStates($objs))->not->toContain(AlertState::RECOVERED, 'An ignored device must not get a recovery from the race path');
});

test('grouped race recovery not sent when parent down', function () {
    $parent = Device::factory()->create(['status' => 0, 'ignore' => 0, 'disabled' => 0]);
    $context = makeActiveAlert([
        ['type' => 'api', 'start' => 300, 'dur' => 3600],
    ]);
    DB::table('device_relationships')->insert([
        'parent_device_id' => $parent->device_id,
        'child_device_id' => $context['device']->device_id,
    ]);

    $objs = runRecoveryRace($context['rule'], $context['device']);

    expect(issuedStates($objs))->not->toContain(AlertState::RECOVERED, 'A recovery must not be sent from the race path while the parent is down');
});

/**
 * Build a device + alert rule (optionally with an operation/segments/transports) and an
 * open, active alert ready for RunAlerts to process.
 *
 * @param  array<int, array{type?:string, from?:int, to?:int|null, start?:int, dur?:int}>  $segmentsConfig
 * @return array{device: Device, operation: AlertOperation|null, rule: AlertRule, segments: array<int, array{segment: \App\Models\AlertOperationSegment, transport: AlertTransport}>}
 */
function makeActiveAlert(array $segmentsConfig, bool $suppressed = false, bool $assignOperation = true, bool $seedAlert = true): array
{
    $device = Device::factory()->create();

    $operation = null;
    $segments = [];

    if ($assignOperation) {
        $operation = AlertOperation::create([
            'name' => 'op ' . uniqid(),
            'default_operation_step_duration_seconds' => 300,
            'notifications_suppressed' => $suppressed,
        ]);

        foreach ($segmentsConfig as $i => $cfg) {
            $segment = $operation->segments()->create([
                'position' => $i,
                'operation_phase' => 'problem',
                'escalation_step_from' => $cfg['from'] ?? 1,
                'escalation_step_to' => $cfg['to'] ?? null,
                'start_in_seconds' => $cfg['start'] ?? 0,
                'step_duration_seconds' => $cfg['dur'] ?? 300,
            ]);

            $transport = AlertTransport::factory()->create([
                'transport_type' => $cfg['type'] ?? 'api',
                'transport_name' => ($cfg['type'] ?? 'api') . ' ' . uniqid(),
            ]);

            $segment->transportSingles()->syncWithPivotValues([$transport->transport_id], ['target_type' => 'single']);

            $segments[] = ['segment' => $segment, 'transport' => $transport];
        }
    }

    $rule = AlertRule::create([
        'name' => 'rule ' . uniqid(),
        'severity' => 'critical',
        'extra' => [],
        'disabled' => 0,
        'query' => 'SELECT * FROM devices WHERE device_id = ?',
        'builder' => ['condition' => 'AND', 'rules' => []],
        'proc' => null,
        'invert_map' => 0,
        'alert_operation_id' => $operation?->id,
    ]);

    if ($seedAlert) {
        DB::table('alerts')->insert([
            'device_id' => $device->device_id,
            'rule_id' => $rule->id,
            'state' => AlertState::ACTIVE,
            'alerted' => AlertState::CLEAR,
            'open' => 1,
            'info' => json_encode(['open_fault_count' => 1]),
        ]);

        $incident = ['id' => (int) $device->device_id, 'msg' => 'down'];
        $fault = AlertFault::create([
            'rule_id' => $rule->id,
            'device_id' => $device->device_id,
            'entity_key' => (string) $device->device_id,
            'state' => AlertState::ACTIVE,
            'open' => 1,
            'alerted' => 0,
            'details' => ['rule' => [$incident], 'contacts' => []],
        ]);

        DB::table('alert_log')->insert([
            'rule_id' => $rule->id,
            'device_id' => $device->device_id,
            'fault_id' => $fault->id,
            'state' => AlertState::ACTIVE,
            'details' => gzcompress((string) json_encode(['rule' => [$incident], 'contacts' => []]), 9),
            'time_logged' => date('Y-m-d H:i:s'),
        ]);
    }

    return compact('device', 'operation', 'rule', 'segments');
}

/**
 * Run the alerter $times, capturing the transport list passed to each issueAlert() call
 * (issueAlert is stubbed so no real delivery happens, but the full engine pipeline runs).
 *
 * @return array<int, array<int, array<string, mixed>>>
 */
function runAlertsCapturing(int $times = 1): array
{
    $captured = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('issueAlert')->andReturnUsing(function ($alert, $transports = null) use (&$captured) {
        $captured[] = $transports ?? [];

        return true;
    });

    for ($i = 0; $i < $times; $i++) {
        $runAlerts->runAlerts();
    }

    return $captured;
}

/**
 * @return array<int, array<string, mixed>>
 */
function runRecoveryRace(AlertRule $rule, Device $device): array
{
    $fault = AlertFault::query()
        ->where('rule_id', $rule->id)
        ->where('device_id', $device->device_id)
        ->firstOrFail();
    $fault->update([
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
    ]);
    AlertLog::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'fault_id' => $fault->id,
        'state' => AlertState::RECOVERED,
        'details' => ['rule' => $fault->details['rule'] ?? [], 'contacts' => []],
    ]);
    Alert::query()
        ->where('rule_id', $rule->id)
        ->where('device_id', $device->device_id)
        ->update([
            'state' => AlertState::ACTIVE,
            'alerted' => AlertState::ACTIVE,
            'open' => 1,
        ]);

    $objs = [];

    /** @var RunAlerts&\Mockery\MockInterface $runAlerts */
    $runAlerts = Mockery::mock(RunAlerts::class)->makePartial();
    $runAlerts->shouldReceive('extTransports')->andReturnUsing(function ($obj) use (&$objs): void {
        $objs[] = $obj;
    });
    $runAlerts->runAlerts();

    Alert::query()
        ->where('rule_id', $rule->id)
        ->where('device_id', $device->device_id)
        ->update(['state' => AlertState::RECOVERED]);

    $runAlerts->runAlerts();

    return $objs;
}

/**
 * @param  array<int, array<string, mixed>>  $objs
 * @return list<int>
 */
function issuedStates(array $objs): array
{
    return array_map(static fn ($state) => (int) $state, array_column($objs, 'state'));
}

/**
 * @param  array<int, array<string, mixed>>  $objs
 * @return list<int>
 */
function issuedPortIds(array $objs): array
{
    $ids = [];
    foreach ($objs as $obj) {
        foreach ($obj['faults'] ?? [] as $fault) {
            $ids[] = $fault['port_id'];
        }
    }

    return $ids;
}

/**
 * @return array<string, mixed>
 */
function latestAlertLogDetails(int $ruleId): array
{
    $row = DB::table('alert_log')->where('rule_id', $ruleId)->orderByDesc('id')->first();
    if ($row === null || empty($row->details)) {
        return [];
    }

    return json_decode((string) gzuncompress($row->details), true) ?? [];
}

function expireProblemSegmentTimers(int $ruleId): void
{
    $log = AlertLog::query()->where('rule_id', $ruleId)->orderByDesc('id')->first();
    if ($log === null) {
        return;
    }

    $details = is_array($log->details) ? $log->details : [];
    foreach ($details['op_seg'] ?? [] as $segmentId => $state) {
        if (! is_array($state)) {
            continue;
        }
        $details['op_seg'][$segmentId]['last'] = 1;
    }
    $log->details = $details;
    $log->save();
}