<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Alert;
use App\Models\AlertFault;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\Mempool;
use App\Models\Port;
use App\Models\Processor;
use App\Models\Storage;
use Illuminate\Support\Carbon;
use LibreNMS\Alert\AlertRules;
use LibreNMS\Alert\AlertUtil;
use LibreNMS\Enum\AlertState;
use LibreNMS\Enum\Severity;
use PHPUnit\Framework\Attributes\DataProvider;


beforeEach(function () {
    $this->dbSetUp();
});

afterEach(function () {
    $this->dbTearDown();
});

test('run rules skips under maintenance', function () {
    $device = Device::factory()->create();

    $schedule = \App\Models\AlertSchedule::forceCreate([
        'title' => 'Maintenance',
        'notes' => '',
        'start' => \Carbon\Carbon::now()->subHour(),
        'end' => \Carbon\Carbon::now()->addHour(),
        'recurring' => 0,
        'behavior' => 1, // SkipAlerts
    ]);
    $schedule->devices()->attach($device->device_id);

    $alertRules = new AlertRules($device);
    $result = $alertRules->run();

    expect($result)->toBeFalse();
});

test('run rules disable alerting', function () {
    $device = Device::factory()->create(['disable_notify' => 1]);
    $rule = AlertRule::factory()->create();

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => 0,
        'info' => [],
    ]);

    $alertRules = new AlertRules($device);
    $result = $alertRules->run();

    expect($result)->toBeFalse();
    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::CLEAR,
        'open' => 0,
    ]);
});

test('run rules triggers alert', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
    ]);

    $this->assertDatabaseHas('alert_log', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);

    // The alert_log entry must be linked to the fault it was raised for. Guards against
    // fault_id being silently dropped by mass-assignment protection (missing from $fillable).
    $fault = AlertFault::query()
        ->where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->first();
    expect($fault)->not->toBeNull('A fault should be created for the triggered alert');

    $log = AlertLog::query()
        ->where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('state', AlertState::ACTIVE)
        ->latest('id')
        ->first();
    expect($log)->not->toBeNull('An alert_log entry should be created for the triggered alert');
    expect($log->fault_id)->not->toBeNull('alert_log.fault_id should be populated');
    expect($log->fault_id)->toBe($fault->id, 'alert_log should be linked to the fault via fault_id');
});

test('run rules updates existing alert', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    // Pre-existing recovered alert
    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => 0,
        'info' => [],
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);
});

test('run rules no change on active alert', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => 0,
        'info' => ['open_fault_count' => 1],
    ]);

    AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => (string) $device->device_id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => 0,
        'details' => ['old' => 'data'],
    ]);

    $logCount = AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->count();

    $alertRules = new AlertRules($device);
    $alertRules->run();

    // State should still be ACTIVE
    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);

    expect(AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->count())->toEqual($logCount);

    $problem = AlertFault::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->first();
    expect($problem->details)->toHaveKey('contacts');
    expect($problem->details)->toHaveKey('rule');
});

test('run rules keeps acknowledged alert until clear when unchanged', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();
    acknowledgeOpenFaults($rule, $device, untilClear: true);

    $activeLogs = AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('state', AlertState::ACTIVE)
        ->count();

    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACKNOWLEDGED,
    ]);
    expect(AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('state', AlertState::ACTIVE)
        ->count())->toEqual($activeLogs);
    $this->assertDatabaseHas('alert_faults', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACKNOWLEDGED,
        'open' => 1,
    ]);
});

test('run rules keeps acknowledged until change when unchanged', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();
    acknowledgeOpenFaults($rule, $device, untilClear: false);

    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACKNOWLEDGED,
    ]);
    $this->assertDatabaseHas('alert_faults', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACKNOWLEDGED,
        'open' => 1,
    ]);
});

test('run rules records recovery while acknowledged until clear', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();
    acknowledgeOpenFaults($rule, $device, untilClear: true);

    $device->status = 1;
    $device->save();
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
    ]);
    $this->assertDatabaseHas('alert_log', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
    ]);
    $this->assertDatabaseHas('alert_faults', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
    ]);
});

test('run rules records new entity while acknowledged until clear', function () {
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);
    $acked = Processor::factory()->for($device)->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();
    $ackedFault = faultForProcessor($rule, $device, $acked);
    acknowledgeOpenFaults($rule, $device, untilClear: true);

    Processor::factory()->for($device)->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 97,
    ]);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::WORSE,
    ]);
    expect($ackedFault->fresh()->state)->toBe(AlertState::ACKNOWLEDGED);
    expect(AlertFault::query()
        ->where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('state', AlertState::ACTIVE)
        ->where('open', 1)
        ->count())->toEqual(1);
});

test('run rules unacknowledges when until clear is false and worse', function () {
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);
    $acked = Processor::factory()->for($device)->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();
    $ackedFault = faultForProcessor($rule, $device, $acked);
    acknowledgeOpenFaults($rule, $device, untilClear: false);

    Processor::factory()->for($device)->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 97,
    ]);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::WORSE,
    ]);
    expect($ackedFault->fresh()->state)->toBe(AlertState::ACTIVE);
    expect(AlertFault::query()
        ->where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('state', AlertState::ACTIVE)
        ->where('open', 1)
        ->count())->toEqual(2);
});

test('run rules unacknowledges when until clear is false and better', function () {
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);
    $keep = Processor::factory()->for($device)->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);
    $recover = Processor::factory()->for($device)->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 96,
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();
    $keepFault = faultForProcessor($rule, $device, $keep);
    $recoverFault = faultForProcessor($rule, $device, $recover);
    acknowledgeOpenFaults($rule, $device, untilClear: false);

    $recover->processor_usage = 10;
    $recover->save();
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::BETTER,
    ]);
    expect($recoverFault->fresh()->state)->toBe(AlertState::RECOVERED);
    expect($keepFault->fresh()->state)->toBe(AlertState::ACTIVE);
});

test('run rules unacknowledges when until clear is false and changed', function () {
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);
    $keep = Processor::factory()->for($device)->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);
    $recover = Processor::factory()->for($device)->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 96,
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();
    $keepFault = faultForProcessor($rule, $device, $keep);
    acknowledgeOpenFaults($rule, $device, untilClear: false);

    $recover->processor_usage = 10;
    $recover->save();
    Processor::factory()->for($device)->create([
        'processor_index' => '3',
        'processor_type' => 'hr',
        'processor_usage' => 99,
    ]);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::CHANGED,
    ]);
    expect($keepFault->fresh()->state)->toBe(AlertState::ACTIVE);
    expect(AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('state', AlertState::CHANGED)
        ->count())->toEqual(1);
});

/**
 * @return iterable<string, array{list<int>, list<int>, bool, int, bool, bool}>
 */
dataset('acknowledgementModes', function () {
    $changes = [
        'unchanged per entity' => [[0], [0], true, 1, true],
        'unchanged grouped' => [[0], [0], false, 1, true],
        'per entity addition at limit' => [[0], [0, 1], true, 2, true],
        'per entity removal' => [[0, 1], [0], true, 2, true],
        'per entity replacement' => [[0, 1], [0, 2], true, 2, true],
        'grouped addition' => [[0], [0, 1], false, 2, false],
        'grouped removal' => [[0, 1], [0], false, 2, false],
        'grouped replacement' => [[0, 1], [0, 2], false, 2, false],
        'cross into grouped' => [[0, 1], [0, 1, 2], true, 2, false],
        'cross back into per entity' => [[0, 1, 2], [0, 1], true, 2, false],
        'stay above limit' => [[0, 1, 2], [0, 1], true, 1, false],
    ];

    foreach ($changes as $name => [$before, $after, $perEntity, $limit, $preserve]) {
        foreach ([false, true] as $untilClear) {
            yield $name . ($untilClear ? ' until clear' : ' until change') => [
                $before, $after, $perEntity, $limit, $untilClear, $preserve || $untilClear,
            ];
        }
    }
});

test('fault acknowledgements follow effective mode', function (array $before, array $after, bool $perEntity, int $limit, bool $untilClear, bool $preserve) {
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM ports WHERE device_id = ? AND ifOperStatus = "down"',
        'notify_per_entity' => $perEntity,
        'max_entities' => $limit,
    ]);
    $ports = [];
    for ($i = 0; $i < 3; $i++) {
        $ports[$i] = Port::factory()->for($device)->create([
            'ifOperStatus' => in_array($i, $before, true) ? 'down' : 'up',
        ]);
    }

    $rules = new AlertRules($device);
    $rules->run();
    acknowledgeOpenFaults($rule, $device, $untilClear);
    AlertFault::query()->where('rule_id', $rule->id)->update(['alerted' => AlertState::ACKNOWLEDGED]);
    $unchanged = AlertFault::query()->where('entity_id', $ports[0]->port_id)->firstOrFail();
    $timestamp = $unchanged->timestamp;

    foreach ($ports as $i => $port) {
        $port->update(['ifOperStatus' => in_array($i, $after, true) ? 'down' : 'up']);
    }
    $rules->run();

    foreach ($ports as $i => $port) {
        if (! in_array($i, $before, true) && ! in_array($i, $after, true)) {
            continue;
        }
        $fault = AlertFault::query()->where('rule_id', $rule->id)
            ->where('entity_id', $port->port_id)->firstOrFail();
        $recovered = ! in_array($i, $after, true);
        $added = ! in_array($i, $before, true);
        $expected = $recovered ? AlertState::RECOVERED
            : ($preserve && ! $added ? AlertState::ACKNOWLEDGED : AlertState::ACTIVE);
        expect((int) $fault->state)->toBe($expected);
        expect((int) $fault->alerted)->toBe($recovered || ($preserve && ! $added) ? AlertState::ACKNOWLEDGED : 0);
        expect(AlertLog::query()->where('fault_id', $fault->id)->orderBy('id')->toBase()->pluck('state')->all())->toBe($recovered ? [AlertState::ACTIVE, AlertState::RECOVERED] : [AlertState::ACTIVE]);
    }

    expect($unchanged->fresh()->timestamp)->toEqual($timestamp);
    expect(AlertUtil::openEntityCountForRuleDevice($rule->id, $device->device_id))->toBe(count($after));

    // A repeat poll must not add history.
    $logCount = AlertLog::query()->count();
    $rules->run();
    expect(AlertLog::query()->count())->toBe($logCount);
})->with('acknowledgementModes');

test('pending recoveries do not count towards acknowledgement mode', function () {
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM ports WHERE device_id = ? AND ifOperStatus = "down"',
        'notify_per_entity' => true,
        'max_entities' => 2,
    ]);
    $a = Port::factory()->for($device)->create(['ifOperStatus' => 'down']);
    $b = Port::factory()->for($device)->create(['ifOperStatus' => 'down']);
    $c = Port::factory()->for($device)->create(['ifOperStatus' => 'up']);
    $rules = new AlertRules($device);
    $rules->run();
    $b->update(['ifOperStatus' => 'up']);
    $rules->run();
    acknowledgeOpenFaults($rule, $device, untilClear: false);

    // B remains open waiting for dispatch, while C brings the active count to the limit.
    $c->update(['ifOperStatus' => 'down']);
    $rules->run();
    expect((int) AlertFault::query()
        ->where('rule_id', $rule->id)->where('entity_id', $a->port_id)->value('state'))->toBe(AlertState::ACKNOWLEDGED);
    expect(AlertFault::query()->where('rule_id', $rule->id)->where('open', 1)->count())->toBe(3);
    expect(AlertUtil::openEntityCountForRuleDevice($rule->id, $device->device_id))->toBe(2);
});

test('acknowledgement mode is scoped to rule and device', function () {
    $a = Device::factory()->create();
    $b = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM ports WHERE device_id = ? AND ifOperStatus = "down"',
        'notify_per_entity' => true,
        'max_entities' => 1,
    ]);
    Port::factory()->for($a)->create(['ifOperStatus' => 'down']);
    $bPort = Port::factory()->for($b)->create(['ifOperStatus' => 'down']);
    $aRules = new AlertRules($a);
    $bRules = new AlertRules($b);
    $aRules->run();
    $bRules->run();
    acknowledgeOpenFaults($rule, $a, untilClear: false);
    acknowledgeOpenFaults($rule, $b, untilClear: false);
    AlertFault::query()->where('rule_id', $rule->id)->update(['alerted' => AlertState::ACKNOWLEDGED]);

    // Only A crosses into grouped mode. B's count must not contribute to that decision.
    Port::factory()->for($a)->create(['ifOperStatus' => 'down']);
    $aRules->run();
    $bRules->run();
    expect(AlertFault::query()->where('device_id', $a->device_id)
        ->where('rule_id', $rule->id)->where('state', AlertState::ACTIVE)->count())->toBe(2);
    $this->assertDatabaseHas('alert_faults', [
        'rule_id' => $rule->id,
        'device_id' => $b->device_id,
        'entity_id' => $bPort->port_id,
        'state' => AlertState::ACKNOWLEDGED,
        'alerted' => AlertState::ACKNOWLEDGED,
    ]);

    $otherRule = AlertRule::factory()->create([
        'query' => $rule->query,
        'notify_per_entity' => true,
        'max_entities' => 2,
    ]);
    $aRules->run();
    acknowledgeOpenFaults($otherRule, $a, untilClear: false);
    $aRules->run();
    expect(AlertFault::query()->where('rule_id', $otherRule->id)
        ->where('state', AlertState::ACKNOWLEDGED)->count())->toBe(2);
});

test('run rules re notifies when entities replaced while already changed', function () {
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM processors WHERE device_id = ? AND processor_usage >= 90',
    ]);
    $a = Processor::factory()->for($device)->create([
        'processor_index' => '1',
        'processor_type' => 'hr',
        'processor_usage' => 95,
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $a->processor_usage = 10;
    $a->save();
    $b = Processor::factory()->for($device)->create([
        'processor_index' => '2',
        'processor_type' => 'hr',
        'processor_usage' => 96,
    ]);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::CHANGED,
    ]);

    // Dispatcher has already notified the first CHANGED.
    Alert::query()
        ->where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->update(['alerted' => AlertState::CHANGED]);

    $b->processor_usage = 10;
    $b->save();
    Processor::factory()->for($device)->create([
        'processor_index' => '3',
        'processor_type' => 'hr',
        'processor_usage' => 99,
    ]);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::CHANGED,
        'open' => 1,
        'alerted' => 0,
    ]);
    expect(AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('state', AlertState::CHANGED)
        ->count())->toEqual(2, 'A second same-count replacement must log another CHANGED transition');
});

test('sync alert state for rule marks acknowledged when all faults acked', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'info' => ['open_fault_count' => 1],
    ]);

    AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => 'device|' . $device->device_id,
        'state' => AlertState::ACKNOWLEDGED,
        'open' => 1,
        'alerted' => 0,
        'details' => ['rule' => []],
    ]);

    new AlertRules($device)->syncAlertState($rule);

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACKNOWLEDGED,
    ]);
});

test('sync alert state logs worse transition when requested', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    // Rule was already alerting on a single fault.
    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'info' => ['open_fault_count' => 1],
    ]);

    // Two open faults now exist -> escalation (worse) versus the recorded count of 1.
    foreach (['a', 'b'] as $i => $key) {
        AlertFault::create([
            'rule_id' => $rule->id,
            'device_id' => $device->device_id,
            'entity_key' => 'entity|' . $key,
            'state' => AlertState::ACTIVE,
            'open' => 1,
            'alerted' => 0,
            'details' => ['rule' => [['id' => $i + 1, 'entity' => $key]]],
        ]);
    }

    new AlertRules($device)->syncAlertState($rule, logStateChange: true);

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::WORSE,
    ]);

    $log = AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('state', AlertState::WORSE)
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull('A rule-level worse alert_log entry should be created');
    expect($log->fault_id)->toBeNull('Rule-level history entries are not tied to a single fault');
    expect($log->details['rule'] ?? [])->toHaveCount(2, 'Worse entry should snapshot all open fault rows');
});

test('sync alert state does not log worse transition by default', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'info' => ['open_fault_count' => 1],
    ]);

    foreach (['a', 'b'] as $key) {
        AlertFault::create([
            'rule_id' => $rule->id,
            'device_id' => $device->device_id,
            'entity_key' => 'entity|' . $key,
            'state' => AlertState::ACTIVE,
            'open' => 1,
            'alerted' => 0,
            'details' => ['rule' => [['entity' => $key]]],
        ]);
    }

    // Default (no flag): the dispatch/API recompute path must not create history rows.
    new AlertRules($device)->syncAlertState($rule);

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::WORSE,
    ]);

    expect(AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('state', AlertState::WORSE)
        ->count())->toEqual(0, 'No rule-level worse entry should be logged without the flag');
});

test('sync alert state retains alerted on recovery so recovery notifies', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    // Rule had an active, already-notified alert (dispatcher advanced alerted to ACTIVE).
    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'info' => ['open_fault_count' => 1],
    ]);

    // The only fault has recovered (still open, pending the recovery notification).
    AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => (string) $device->device_id,
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => 0,
        'details' => ['rule' => []],
    ]);

    new AlertRules($device)->syncAlertState($rule);

    // RECOVERED shares value 0 with the "reset alerted" sentinel. alerted must be left at its
    // previous (active) value so the dispatcher sees alerted != state and sends the recovery;
    // zeroing it would make runAlerts skip the recovery notification.
    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
        'alerted' => AlertState::ACTIVE,
    ]);
});

test('sync alert state restores alerted on recovery after better reset', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::BETTER,
        'open' => 1,
        'alerted' => 0,
        'info' => ['open_fault_count' => 1],
    ]);

    AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => 'entity|a',
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::WORSE,
        'details' => ['rule' => []],
    ]);

    new AlertRules($device)->syncAlertState($rule);

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
        'alerted' => AlertState::WORSE,
        'open' => 1,
    ]);
});

test('sync alert state does not restore alerted when problem was never notified', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::BETTER,
        'open' => 1,
        'alerted' => 0,
        'info' => ['open_fault_count' => 1],
    ]);

    AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => 'entity|a',
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => 0,
        'details' => ['rule' => []],
    ]);

    new AlertRules($device)->syncAlertState($rule);

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
        'alerted' => 0,
    ]);
});

test('run rules clears alert', function () {
    $device = Device::factory()->create(['status' => 1]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => 0,
        'info' => ['open_fault_count' => 1],
    ]);

    AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => (string) $device->device_id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => 0,
        'details' => ['old' => 'data'],
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
    ]);

    $this->assertDatabaseHas('alert_log', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
    ]);

    $this->assertDatabaseHas('alert_faults', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
        'alerted' => 0,
    ]);
});

test('run rules preserves fault alerted on recovery', function () {
    $device = Device::factory()->create(['status' => 1]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'info' => ['open_fault_count' => 1],
    ]);

    AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => (string) $device->device_id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'details' => ['old' => 'data'],
    ]);

    new AlertRules($device)->run();

    $this->assertDatabaseHas('alert_faults', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
    ]);
});

test('run rules reactivates open recovered fault when entity returns', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'info' => ['open_fault_count' => 0],
    ]);

    $fault = AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => (string) $device->device_id,
        'state' => AlertState::RECOVERED,
        'open' => 1,
        'alerted' => AlertState::ACTIVE,
        'details' => ['rule' => []],
    ]);

    new AlertRules($device)->run();

    expect(AlertFault::query()
        ->where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->where('open', 1)
        ->count())->toBe(1, 'Must reuse the open recovered row, not insert a second fault');
    expect((int) $fault->fresh()->state)->toBe(AlertState::ACTIVE);
    expect((int) $fault->fresh()->alerted)->toBe(0);
    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);
});

test('run rules inverts result', function () {
    $device = Device::factory()->create(['status' => 1]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 1',
        'extra' => ['invert' => true],
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    // status=1 matches query, but invert=true, so doalert=false
    $this->assertDatabaseMissing('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);
});

test('run rules handles query error', function () {
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT invalid_column FROM devices WHERE device_id = ?',
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('eventlog', [
        'device_id' => $device->device_id,
        'type' => 'alert',
        'severity' => Severity::Error,
    ]);
});

test('rules filtering', function () {
    $device = Device::factory()->create(['status' => 0]);
    $otherDevice = Device::factory()->create(['status' => 0]);

    // 1. Rule mapped to this device - should trigger
    $ruleMapped = AlertRule::factory()->create([
        'name' => 'Mapped Rule',
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);
    $ruleMapped->devices()->attach($device->device_id);

    // 2. Rule mapped to OTHER device - should NOT trigger
    $ruleOtherMapped = AlertRule::factory()->create([
        'name' => 'Other Mapped Rule',
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);
    $ruleOtherMapped->devices()->attach($otherDevice->device_id);

    // 3. Global rule - should trigger
    $ruleGlobal = AlertRule::factory()->create([
        'name' => 'Global Rule',
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $ruleMapped->id,
        'state' => AlertState::ACTIVE,
    ]);

    $this->assertDatabaseMissing('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $ruleOtherMapped->id,
    ]);

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $ruleGlobal->id,
        'state' => AlertState::ACTIVE,
    ]);
});

test('rules filtering inverted', function () {
    $device = Device::factory()->create(['status' => 0]);

    // 1. Rule mapped to this device, BUT inverted - should NOT trigger
    $ruleInvertedMapped = AlertRule::factory()->create([
        'name' => 'Inverted Mapped Rule',
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
        'invert_map' => 1,
    ]);
    $ruleInvertedMapped->devices()->attach($device->device_id);

    // 2. Rule mapped to OTHER device, inverted - should trigger
    $otherDevice = Device::factory()->create();
    $ruleInvertedOtherMapped = AlertRule::factory()->create([
        'name' => 'Inverted Other Mapped Rule',
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
        'invert_map' => 1,
    ]);
    $ruleInvertedOtherMapped->devices()->attach($otherDevice->device_id);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseMissing('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $ruleInvertedMapped->id,
    ]);

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $ruleInvertedOtherMapped->id,
        'state' => AlertState::ACTIVE,
    ]);
});

test('rules filtering groups', function () {
    $device = Device::factory()->create(['status' => 0]);
    [$group, $otherGroup] = \App\Models\DeviceGroup::factory()->count(2)->create();
    $group->devices()->attach($device->device_id);

    $rule = AlertRule::factory()->create([
        'name' => 'Group Rule',
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);
    $rule->groups()->attach($group->id);

    $otherRule = AlertRule::factory()->create([
        'name' => 'Other Group Rule',
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);
    $otherRule->groups()->attach($otherGroup->id);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);

    $this->assertDatabaseMissing('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $otherRule->id,
    ]);
});

test('rules filtering locations', function () {
    $location = \App\Models\Location::factory()->create();
    $device = Device::factory()->create(['status' => 0, 'location_id' => $location->id]);

    $rule = AlertRule::factory()->create([
        'name' => 'Location Rule',
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);
    $rule->locations()->attach($location->id);

    $otherLocation = \App\Models\Location::factory()->create();
    $otherRule = AlertRule::factory()->create([
        'name' => 'Other Location Rule',
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);
    $otherRule->locations()->attach($otherLocation->id);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);

    $this->assertDatabaseMissing('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $otherRule->id,
    ]);
});

test('run rules handles worse state as no change', function () {
    runActiveStateTest(AlertState::WORSE);
});

test('run rules handles better state as no change', function () {
    runActiveStateTest(AlertState::BETTER);
});

test('run rules handles changed state as no change', function () {
    runActiveStateTest(AlertState::CHANGED);
});

function faultForProcessor(AlertRule $rule, Device $device, Processor $processor): AlertFault
{
    $fault = AlertFault::query()
        ->where('rule_id', $rule->id)
        ->where('device_id', $device->device_id)
        ->where('entity_key', AlertUtil::faultKeyForRow($processor->toArray()))
        ->first();

    expect($fault)->not->toBeNull('Expected a fault for processor ' . $processor->processor_id);

    return $fault;
}

function acknowledgeOpenFaults(AlertRule $rule, Device $device, bool $untilClear): void
{
    $faults = AlertFault::query()
        ->where('rule_id', $rule->id)
        ->where('device_id', $device->device_id)
        ->where('open', 1)
        ->where('state', '!=', AlertState::RECOVERED)
        ->get();

    expect($faults)->not->toBeEmpty('Expected open faults to acknowledge');

    foreach ($faults as $fault) {
        $info = is_array($fault->info) ? $fault->info : [];
        $info['until_clear'] = $untilClear;
        $fault->info = $info;
        $fault->state = AlertState::ACKNOWLEDGED;
        $fault->save();
    }

    new AlertRules($device)->syncAlertState($rule);
}

function runActiveStateTest(int $state): void
{
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    $initialTimestamp = \Carbon\Carbon::now()->subHour();

    // Pre-existing alert in given state
    $alert = Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => $state,
        'open' => 1,
        'alerted' => 1,
        'info' => ['open_fault_count' => 1],
        'timestamp' => $initialTimestamp,
    ]);

    AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => (string) $device->device_id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => 0,
        'details' => ['old' => 'data'],
    ]);

    $logCount = AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->count();

    $alertRules = new AlertRules($device);
    $alertRules->run();

    // State should still be the same, not reset to ACTIVE
    $alert->refresh();
    expect($alert->state)->toEqual($state, "Alert state was reset from $state to ACTIVE");

    // Timestamp should NOT have changed
    $actual = $alert->timestamp instanceof Carbon ? $alert->timestamp->toDateTimeString() : $alert->timestamp;
    expect($actual)->toEqual($initialTimestamp->toDateTimeString(), 'Alert timestamp was reset');

    expect(AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->count())->toEqual($logCount);

    $problem = AlertFault::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->first();
    expect($problem->details)->toHaveKey('contacts');
}

test('run rules with device id', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    $alertRules = new AlertRules($device->device_id);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);
});

test('run rules skips disabled rule', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
        'disabled' => 1,
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseMissing('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
    ]);
});

test('run rules uses query builder json', function () {
    $device = Device::factory()->create(['status' => 0]);
    $builderJson = [
        'condition' => 'AND',
        'rules' => [
            [
                'field' => 'devices.status',
                'operator' => 'equal',
                'value' => '0',
            ],
        ],
    ];
    $rule = AlertRule::factory()->create([
        'query' => '',
        'builder' => $builderJson,
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);
});

test('run rules skips empty sql', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => '',
        'builder' => [],
    ]);

    $alertRules = new AlertRules($device);
    $result = $alertRules->run();

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
    ]);
});

test('run rules converts binary ip', function () {
    $device = Device::factory()->create(['status' => 0]);

    // MySQL INET6_ATON converts string to binary
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT INET6_ATON("192.0.2.1") AS ip FROM devices WHERE device_id = ?',
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $updatedLog = AlertLog::where('device_id', $device->device_id)
        ->where('rule_id', $rule->id)
        ->latest('id')
        ->first();

    expect($updatedLog)->not->toBeNull();
    $details = $updatedLog->details;
    expect($details['rule'][0]['ip'])->toEqual('192.0.2.1');
});

test('run rules recovery without pre existing alert', function () {
    $device = Device::factory()->create(['status' => 1]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::RECOVERED,
    ]);
});

test('run rules no change on active alert without log', function () {
    $device = Device::factory()->create(['status' => 0]);
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
    ]);

    Alert::create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => 0,
        'info' => [],
    ]);

    // Note: No AlertLog is created in DB for this rule.
    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);
    // AlertLog was not created beforehand, and shouldn't cause errors.
});

test('run rules inverts result to trigger alert', function () {
    $device = Device::factory()->create(['status' => 1]);

    // The query returns empty for status = 0.
    // With invert = true, empty means trigger alert (do_alert = true).
    $rule = AlertRule::factory()->create([
        'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
        'extra' => ['invert' => true],
    ]);

    $alertRules = new AlertRules($device);
    $alertRules->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);
});

/**
 * @return array<string, array{class-string<Processor|Mempool|Storage>, string, string, string}>
 */
dataset('warnThresholdModels', function () {
    return [
        'processor' => [Processor::class, 'processors', 'processor_usage', 'processor_perc_warn'],
        'mempool' => [Mempool::class, 'mempools', 'mempool_perc', 'mempool_perc_warn'],
        'storage' => [Storage::class, 'storage', 'storage_perc', 'storage_perc_warn'],
    ];
});

test('warn threshold rule respects null threshold', function (string $modelClass, string $table, string $percentField, string $warnField) {
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create([
        'query' => '',
        'builder' => [
            'condition' => 'AND',
            'rules' => [
                [
                    'id' => "$table.$percentField",
                    'field' => "$table.$percentField",
                    'type' => 'string',
                    'input' => 'text',
                    'operator' => 'greater_or_equal',
                    'value' => "`$table.$warnField`",
                ],
            ],
            'valid' => true,
        ],
    ]);

    $model = $modelClass::factory()->for($device)->createQuietly([$percentField => 95, $warnField => null]);

    new AlertRules($device)->run();

    $this->assertDatabaseMissing('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);

    $model->forceFill([$warnField => 90])->saveQuietly();

    new AlertRules($device)->run();

    $this->assertDatabaseHas('alerts', [
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'state' => AlertState::ACTIVE,
    ]);
})->with('warnThresholdModels');
