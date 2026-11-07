<?php

/**
 * AlertFaultAcknowledgementTest.php
 *
 * Acknowledging / un-acknowledging grouped alerts must not touch faults that have
 * recovered and are only still open waiting for their recovery notification.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 LibreNMS
 */

namespace LibreNMS\Tests\Feature\Alert;

use App\Models\Alert;
use App\Http\Middleware\CheckInstalled;
use App\Models\AlertFault;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\User;
use LibreNMS\Alert\AlertRules;
use LibreNMS\Enum\AlertState;
use LibreNMS\Tests\InMemoryDbTestCase;

final class AlertFaultAcknowledgementTest extends InMemoryDbTestCase
{
    private Device $device;
    private AlertRule $rule;
    private AlertFault $active;
    private AlertFault $recovered;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CheckInstalled::class);

        $this->device = Device::factory()->create();
        $this->rule = AlertRule::factory()->create(['notify_per_entity' => false]);
        $this->active = $this->makeFault('port:1', AlertState::ACTIVE);
        $this->recovered = $this->makeFault('port:2', AlertState::RECOVERED, alerted: AlertState::ACTIVE);
        (new AlertRules($this->device))->syncAlertState($this->rule);
    }

    private function makeFault(string $key, int $state, int $alerted = 0): AlertFault
    {
        return AlertFault::query()->create([
            'rule_id' => $this->rule->id,
            'device_id' => $this->device->device_id,
            'entity_key' => $key,
            'state' => $state,
            'alerted' => $alerted,
            'open' => 1,
            'info' => [],
            'details' => [],
        ]);
    }

    private function alertRow(): Alert
    {
        return Alert::query()->where('rule_id', $this->rule->id)->where('device_id', $this->device->device_id)->firstOrFail();
    }

    private function assertRecoveredUntouched(): void
    {
        $recovered = $this->recovered->fresh();
        $this->assertSame(AlertState::RECOVERED, (int) $recovered->state);
        $this->assertSame(1, (int) $recovered->open);
        $this->assertSame(AlertState::ACTIVE, (int) $recovered->alerted);
        $this->assertEmpty($recovered->note);
    }

    private function apiHeaders(): array
    {
        $user = User::factory()->admin()->create(['enabled' => true]);

        return ['X-Auth-Token' => $user->createToken('test')->plainTextToken];
    }

    public function testTargetsExcludePendingRecoveries(): void
    {
        $this->assertTrue($this->active->canAcknowledge());
        $this->assertFalse($this->recovered->canAcknowledge());
        $this->assertFalse($this->recovered->canUnacknowledge());
        $this->assertSame([$this->active->id], $this->active->acknowledgementTargets(true)->modelKeys());
        $this->assertSame([], $this->active->acknowledgementTargets(false)->modelKeys());
    }

    public function testWebAckLeavesRecoveredFaultAlone(): void
    {
        $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
            ->postJson(route('alert.ack', $this->active), ['state' => AlertState::ACTIVE, 'ack_msg' => 'on it'])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertSame(AlertState::ACKNOWLEDGED, (int) $this->active->fresh()->state);
        $this->assertRecoveredUntouched();
        $alert = $this->alertRow();
        $this->assertSame(AlertState::ACKNOWLEDGED, (int) $alert->state);
        $this->assertSame(1, $alert->info['open_fault_count']);
    }

    public function testWebUnackLeavesRecoveredFaultAlone(): void
    {
        $this->active->update(['state' => AlertState::ACKNOWLEDGED]);
        (new AlertRules($this->device))->syncAlertState($this->rule);

        $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
            ->postJson(route('alert.ack', $this->active), ['state' => AlertState::ACKNOWLEDGED])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertSame(AlertState::ACTIVE, (int) $this->active->fresh()->state);
        $this->assertRecoveredUntouched();
        $alert = $this->alertRow();
        $this->assertSame(AlertState::ACTIVE, (int) $alert->state);
        $this->assertSame(1, $alert->info['open_fault_count']);
    }

    public function testWebAckUsesStoredStateNotClientState(): void
    {
        // A stale page still believes the fault is active; it must not ack a recovered fault.
        $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
            ->postJson(route('alert.ack', $this->recovered), ['state' => AlertState::ACTIVE])
            ->assertOk()
            ->assertJson(['status' => 'error']);
        $this->assertRecoveredUntouched();
        $this->assertSame(AlertState::ACTIVE, (int) $this->active->fresh()->state);

        // A stale page believes the fault is acknowledged, but it is active: acknowledge it.
        $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
            ->postJson(route('alert.ack', $this->active), ['state' => AlertState::ACKNOWLEDGED])
            ->assertOk()
            ->assertJson(['status' => 'ok']);
        $this->assertSame(AlertState::ACKNOWLEDGED, (int) $this->active->fresh()->state);
    }

    public function testApiAckLeavesRecoveredFaultAlone(): void
    {
        $this->putJson("/api/v0/faults/{$this->active->id}", ['note' => 'api', 'until_clear' => true], $this->apiHeaders())
            ->assertOk();

        $this->assertSame(AlertState::ACKNOWLEDGED, (int) $this->active->fresh()->state);
        $this->assertRecoveredUntouched();
        $this->assertSame(AlertState::ACKNOWLEDGED, (int) $this->alertRow()->state);
    }

    public function testApiUnmuteLeavesRecoveredFaultAlone(): void
    {
        $this->active->update(['state' => AlertState::ACKNOWLEDGED]);
        (new AlertRules($this->device))->syncAlertState($this->rule);

        $this->putJson("/api/v0/faults/unmute/{$this->active->id}", ['note' => 'api'], $this->apiHeaders())
            ->assertOk();

        $this->assertSame(AlertState::ACTIVE, (int) $this->active->fresh()->state);
        $this->assertRecoveredUntouched();
        $alert = $this->alertRow();
        $this->assertSame(AlertState::ACTIVE, (int) $alert->state);
        $this->assertSame(1, $alert->info['open_fault_count']);
    }
}
