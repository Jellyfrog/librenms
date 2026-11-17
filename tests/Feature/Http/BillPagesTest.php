<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Bill;
use App\Models\BillHistory;
use App\Models\BillPerm;
use App\Models\Device;
use App\Models\Port;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');

    foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
        Permission::findOrCreate("bill.$ability");
    }

    LibrenmsConfig::set('enable_billing', 1);
    LibrenmsConfig::set('billing.base', 1000);
});

test('index page renders', function (): void {
    $this->actingAs(billAdmin())
        ->get(route('bills.index'))
        ->assertOk()
        ->assertSee(route('table.bills'), false)
        ->assertSee(__('Add Traffic Bill'));
});

test('index prefills create form from port', function (): void {
    $device = Device::factory()->create();
    $port = Port::factory()->create([
        'device_id' => $device->device_id,
        'port_descr_descr' => 'Customer Uplink',
        'port_descr_circuit' => 'CIRCUIT-42',
    ]);

    $this->actingAs(billAdmin())
        ->get(route('bills.index', ['port' => $port->port_id]))
        ->assertOk()
        ->assertSee('value="Customer Uplink"', false)
        ->assertSee('value="CIRCUIT-42"', false)
        ->assertSee('createBill: true', false);
});

test('index requires billing enabled', function (): void {
    LibrenmsConfig::set('enable_billing', 0);

    $this->actingAs(billUser())
        ->get(route('bills.index'))
        ->assertForbidden();
});

test('admin can create cdr bill with port', function (): void {
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id]);

    $response = $this->actingAs(billAdmin())->post(route('bill.store'), [
        'bill_name' => 'New CDR Bill',
        'bill_type' => 'cdr',
        'bill_day' => 3,
        'bill_cdr' => 100,
        'bill_cdr_type' => 'Mbps',
        'bill_quota' => 5,
        'bill_quota_type' => 'GB',
        'dir_95th' => 'agg',
        'port_id' => $port->port_id,
    ]);

    $bill = Bill::where('bill_name', 'New CDR Bill')->firstOrFail();
    $response->assertRedirect(route('bill.edit', $bill));
    expect($bill->bill_type)->toEqual('cdr');
    expect($bill->bill_day)->toEqual(3);
    expect($bill->bill_cdr)->toEqual(100000000);
    expect($bill->bill_quota)->toEqual(0);
    expect($bill->dir_95th)->toEqual('agg');
    $this->assertDatabaseHas('bill_ports', ['bill_id' => $bill->bill_id, 'port_id' => $port->port_id]);
});

test('admin can create quota bill without port', function (): void {
    $this->actingAs(billAdmin())->post(route('bill.store'), [
        'bill_name' => 'New Quota Bill',
        'bill_type' => 'quota',
        'bill_day' => 1,
        'bill_quota' => 2,
        'bill_quota_type' => 'TB',
    ])->assertRedirect();

    $bill = Bill::where('bill_name', 'New Quota Bill')->firstOrFail();
    expect($bill->bill_quota)->toEqual(2000000000000);
    expect($bill->bill_cdr)->toEqual(0);
    expect($bill->dir_95th)->toEqual('in');
    expect($bill->ports()->count())->toEqual(0);
});

test('units honor binary billing base', function (): void {
    LibrenmsConfig::set('billing.base', 1024);

    $this->actingAs(billAdmin())->post(route('bill.store'), [
        'bill_name' => 'Binary Bill',
        'bill_type' => 'quota',
        'bill_day' => 1,
        'bill_quota' => 1.5,
        'bill_quota_type' => 'GB',
    ])->assertRedirect();

    $bill = Bill::where('bill_name', 'Binary Bill')->firstOrFail();
    expect($bill->bill_quota)->toEqual(1610612736);

    $this->actingAs(billAdmin())
        ->get(route('bill.edit', $bill))
        ->assertSee('name="bill_quota" value="1.5"', false)
        ->assertSee('<option value="GB" selected', false);
});

test('create bill validates input', function (): void {
    $this->actingAs(billAdmin())
        ->post(route('bill.store'), ['bill_type' => 'bogus', 'bill_day' => 40])
        ->assertSessionHasErrors(['bill_name', 'bill_type', 'bill_day']);

    expect(Bill::count())->toEqual(0);
});

test('user cannot create bill', function (): void {
    $this->actingAs(billUser())->post(route('bill.store'), [
        'bill_name' => 'Nope',
        'bill_type' => 'quota',
        'bill_day' => 1,
    ])->assertForbidden();

    expect(Bill::count())->toEqual(0);
});

test('admin can view all bill pages', function (): void {
    $admin = billAdmin();
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id, 'ifName' => 'eth-billed', 'ifDescr' => 'eth-billed', 'ifAlias' => 'Billed Uplink']);
    $bill = Bill::factory()->create([
        'bill_name' => 'Viewable Bill',
        'bill_type' => 'quota',
        'bill_quota' => 5000000000,
        'total_data' => 6000000000,
        'total_data_in' => 4000000000,
        'total_data_out' => 2000000000,
    ]);
    $bill->ports()->attach($port->port_id);
    $history = createHistory($bill, now()->subMonths(2), now()->subMonth());

    foreach (['show', 'accurate', 'transfer', 'history', 'edit'] as $page) {
        $response = $this->actingAs($admin)
            ->get(route("bill.$page", $bill))
            ->assertOk()
            ->assertSee('Viewable Bill');

        if ($page !== 'history') {
            $response->assertSee('eth-billed');
        }
    }

    $this->actingAs($admin)
        ->get(route('bill.transfer', $bill))
        ->assertSee(__('Overusage'));

    $this->actingAs($admin)
        ->get(route('bill.history', ['bill' => $bill, 'detail' => $history->bill_hist_id]))
        ->assertSee('bill_hist_id=' . $history->bill_hist_id, false);

    $this->actingAs($admin)
        ->get(route('bill.edit', $bill))
        ->assertSee('name="bill_quota" value="5"', false)
        ->assertSee('<option value="GB" selected', false);
});

test('user without bill access cannot view bill', function (): void {
    $bill = Bill::factory()->create();

    $this->actingAs(billUser())
        ->get(route('bill.show', $bill))
        ->assertForbidden();
});

test('user with bill access can view but not edit', function (): void {
    $user = billUser();
    $bill = Bill::factory()->create(['bill_name' => 'My Bill']);
    BillPerm::query()->insert(['user_id' => $user->user_id, 'bill_id' => $bill->bill_id]);

    $this->actingAs($user)
        ->get(route('bill.show', $bill))
        ->assertOk()
        ->assertSee('My Bill')
        ->assertDontSee(route('bill.edit', $bill))
        ->assertDontSee(__('Delete Bill'));

    $this->actingAs($user)
        ->get(route('bill.edit', $bill))
        ->assertForbidden();
});

test('legacy urls redirect', function (): void {
    $admin = billAdmin();
    $bill = Bill::factory()->create();

    $this->actingAs($admin)->get("bill/bill_id={$bill->bill_id}")
        ->assertRedirect(route('bill.show', $bill));
    $this->actingAs($admin)->get("bill/bill_id={$bill->bill_id}/view=history")
        ->assertRedirect(route('bill.history', $bill));
    $this->actingAs($admin)->get("bill/bill_id={$bill->bill_id}/view=delete")
        ->assertRedirect(route('bill.edit', $bill));
});

test('table lists current period with filters', function (): void {
    $admin = billAdmin();
    $over = Bill::factory()->create(['bill_name' => 'Over Bill', 'bill_type' => 'cdr', 'bill_cdr' => 1000, 'rate_95th' => 2000]);
    Bill::factory()->create(['bill_name' => 'Under Bill', 'bill_type' => 'quota', 'bill_quota' => 1000, 'total_data' => 500]);

    $response = $this->actingAs($admin)->postJson(route('table.bills'), ['current' => 1, 'rowCount' => 50])
        ->assertOk()
        ->assertJsonPath('total', 2);
    $this->assertStringContainsString('Over Bill', $response->json('rows.0.bill_name'));
    expect($response->json('rows.0.bill_type'))->toEqual('CDR');
    $this->assertNotEquals('-', $response->json('rows.0.overusage'));
    $this->assertStringContainsString(route('bill.edit', $over->bill_id), $response->json('rows.0.actions'));
    expect($response->json('rows.1.overusage'))->toEqual('-');

    $response = $this->actingAs($admin)->postJson(route('table.bills'), ['state' => 'over'])
        ->assertJsonPath('total', 1);
    $this->assertStringContainsString('Over Bill', $response->json('rows.0.bill_name'));

    $response = $this->actingAs($admin)->postJson(route('table.bills'), ['bill_type' => 'quota'])
        ->assertJsonPath('total', 1);
    $this->assertStringContainsString('Under Bill', $response->json('rows.0.bill_name'));

    $this->actingAs($admin)->postJson(route('table.bills'), ['searchPhrase' => 'Under'])
        ->assertJsonPath('total', 1);

    $response = $this->actingAs($admin)->postJson(route('table.bills'), ['sort' => ['total_data' => 'desc']])
        ->assertOk();
    $this->assertStringContainsString('Under Bill', $response->json('rows.0.bill_name'));
});

test('table lists previous period', function (): void {
    $admin = billAdmin();
    $bill = Bill::factory()->create(['bill_name' => 'Historic Bill', 'bill_type' => 'quota']);
    Bill::factory()->create(['bill_name' => 'No History Bill']);
    createHistory($bill, now()->subMonth()->subDays(5), now()->subDays(5));

    $response = $this->actingAs($admin)->postJson(route('table.bills'), ['period' => 'prev', 'state' => 'over'])
        ->assertOk()
        ->assertJsonPath('total', 1);

    $row = $response->json('rows.0');
    $this->assertStringContainsString('Historic Bill', $row['bill_name']);
    expect($row['bill_type'])->toEqual('Quota');
    expect($row['predicted'])->toEqual('-');
    expect($row['actions'])->toEqual('');
});

test('table only shows permitted bills', function (): void {
    $user = billUser();
    $mine = Bill::factory()->create(['bill_name' => 'Mine']);
    Bill::factory()->create(['bill_name' => 'Not Mine']);
    BillPerm::query()->insert(['user_id' => $user->user_id, 'bill_id' => $mine->bill_id]);

    $response = $this->actingAs($user)->postJson(route('table.bills'))
        ->assertOk()
        ->assertJsonPath('total', 1);
    $this->assertStringContainsString('Mine', $response->json('rows.0.bill_name'));
    expect($response->json('rows.0.actions'))->toEqual('');
});

function billAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function billUser(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

function createHistory(Bill $bill, \DateTimeInterface $from, \DateTimeInterface $to): BillHistory
{
    return BillHistory::create([
        'bill_id' => $bill->bill_id,
        'bill_datefrom' => $from,
        'bill_dateto' => $to,
        'bill_type' => 'Quota',
        'bill_allowed' => 10000,
        'bill_used' => 15000,
        'bill_overuse' => 5000,
        'bill_percent' => 150,
        'rate_95th_in' => 0,
        'rate_95th_out' => 0,
        'rate_95th' => 0,
        'dir_95th' => 'in',
        'rate_average' => 0,
        'rate_average_in' => 0,
        'rate_average_out' => 0,
        'traf_in' => 10000,
        'traf_out' => 5000,
        'traf_total' => 15000,
        'bill_peak_out' => 100,
        'bill_peak_in' => 200,
    ]);
}