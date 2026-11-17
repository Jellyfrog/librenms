<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\AlertFault;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\User;
use LibreNMS\Enum\AlertState;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
});

test('alerts table namespaces incident ids by widget id', function () {
    $fault = makeFault();

    $rows = $this->actingAs(admin())
        ->postJson(route('table.alerts'), ['widget_id' => 47])
        ->assertOk()
        ->json('rows');

    assertRowHasIncidentId($rows[0], 47, $fault->id);
});

test('alerts table keeps incident ids without widget id', function () {
    $fault = makeFault();

    $rows = $this->actingAs(admin())
        ->postJson(route('table.alerts'))
        ->assertOk()
        ->json('rows');

    assertRowHasIncidentId($rows[0], null, $fault->id);
});

test('alertlog table namespaces incident ids by widget id', function () {
    $log = makeAlertLog();

    $rows = $this->actingAs(admin())
        ->postJson(route('table.alertlog'), ['widget_id' => 47])
        ->assertOk()
        ->json('rows');

    assertRowHasIncidentId($rows[0], 47, $log->id);
});

test('alertlog table keeps incident ids without widget id', function () {
    $log = makeAlertLog();

    $rows = $this->actingAs(admin())
        ->postJson(route('table.alertlog'))
        ->assertOk()
        ->json('rows');

    assertRowHasIncidentId($rows[0], null, $log->id);
});

test('tables reject invalid widget id', function () {
    $admin = admin();

    foreach (['table.alerts', 'table.alertlog'] as $route) {
        foreach (['abc', '47abc', 0, -1] as $widgetId) {
            $this->actingAs($admin)
                ->postJson(route($route), ['widget_id' => $widgetId])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('widget_id');
        }
    }
});

function makeFault(): AlertFault
{
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create();

    return AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => (string) $device->device_id,
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => 1,
        'details' => ['rule' => [['name' => 'test']]],
    ]);
}

function makeAlertLog(): AlertLog
{
    $device = Device::factory()->create();
    $rule = AlertRule::factory()->create();

    return AlertLog::factory()->create([
        'device_id' => $device->device_id,
        'rule_id' => $rule->id,
        'details' => ['rule' => [['name' => 'test']]],
    ]);
}

/**
 * @param  array<string, mixed>  $row
 */
function assertRowHasIncidentId(array $row, ?int $widgetId, int $entityId): void
{
    $incidentId = $widgetId !== null ? "incident{$widgetId}-{$entityId}" : "incident{$entityId}";

    $this->assertStringContainsString("data-target=\"#{$incidentId}\"", $row['details']);
    $this->assertStringContainsString("id=\"{$incidentId}\"", $row['hostname']);
}

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}