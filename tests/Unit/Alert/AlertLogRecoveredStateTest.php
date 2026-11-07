<?php

namespace LibreNMS\Tests\Unit\Alert;

use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Device;
use LibreNMS\Alert\AlertRules;
use LibreNMS\Enum\AlertLogState;
use LibreNMS\Enum\AlertState;
use LibreNMS\Tests\InMemoryDbTestCase;

final class AlertLogRecoveredStateTest extends InMemoryDbTestCase
{
    public function testRecoveryIsLoggedAsRecovered(): void
    {
        $device = Device::factory()->create(['status' => 0]);
        $rule = AlertRule::factory()->create([
            'query' => 'SELECT * FROM devices WHERE device_id = ? AND status = 0',
        ]);

        $alertRules = new AlertRules($device);
        $alertRules->run();

        $device->status = 1;
        $device->save();
        $alertRules->run();

        $states = AlertLog::query()->where('rule_id', $rule->id)->orderBy('id')->pluck('state')->all();
        $this->assertSame([AlertLogState::Active, AlertLogState::Recovered], $states);

        $recovered = AlertLog::query()->applyFilters(['state' => ['eq' => 'recovered']])->get();
        $this->assertCount(1, $recovered);
        $this->assertSame(AlertLogState::Recovered, $recovered->first()->state);
        $this->assertSame(0, AlertLog::query()->applyFilters(['state' => ['eq' => 'alerted']])->where('state', AlertLogState::Recovered)->count());
    }

    public function testMigrationMarksOldRecoveriesRecovered(): void
    {
        $device = Device::factory()->create();
        $rule = AlertRule::factory()->create();
        $old = AlertLog::query()->insertGetId(['device_id' => $device->device_id, 'rule_id' => $rule->id, 'state' => 0]);
        $active = AlertLog::query()->insertGetId(['device_id' => $device->device_id, 'rule_id' => $rule->id, 'state' => 1]);

        $migration = require __DIR__ . '/../../../database/migrations/2026_11_07_000000_alert_log_recovered_state.php';
        $migration->up();

        $this->assertSame(AlertLogState::Recovered, AlertLog::find($old)->state);
        $this->assertSame(AlertLogState::Active, AlertLog::find($active)->state);
    }

    public function testAlertStatesMapToLogStates(): void
    {
        $this->assertSame(AlertLogState::Recovered, AlertLogState::fromAlertState(AlertState::RECOVERED));
        $this->assertSame(AlertLogState::Active, AlertLogState::fromAlertState(AlertState::ACTIVE));
        $this->assertSame(AlertLogState::Acknowledged, AlertLogState::fromAlertState(AlertState::ACKNOWLEDGED));
        $this->assertSame(AlertLogState::Worse, AlertLogState::fromAlertState(AlertState::WORSE));
        $this->assertSame(AlertLogState::Better, AlertLogState::fromAlertState(AlertState::BETTER));
        $this->assertSame(AlertLogState::Changed, AlertLogState::fromAlertState(AlertState::CHANGED));

        $this->expectException(\ValueError::class);
        AlertLogState::fromAlertState(99);
    }
}
