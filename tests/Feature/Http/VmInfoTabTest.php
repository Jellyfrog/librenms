<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Facades\Rrd;
use App\Models\Device;
use App\Models\User;
use App\Models\Vminfo;
use App\View\Components\VminfoGraphs;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

test('shows the recorded graphs of avm', function () {
    $device = Device::factory()->create(['hostname' => 'pve.example.com']);
    $vm = Vminfo::factory()->for($device)->create(['vm_type' => 'proxmox', 'vmwVmVMID' => 100, 'vmwVmDisplayName' => 'web']);

    $rrdDir = sys_get_temp_dir() . '/librenms-vm-test-' . uniqid();
    LibrenmsConfig::set('rrd_dir', $rrdDir);
    LibrenmsConfig::set('rrdcached', '');

    try {
        foreach (['vm.cpu', 'vm.net'] as $metric) {
            $path = Rrd::metricName($vm->metricIdentity($metric))->fullPath();
            File::ensureDirectoryExists(dirname($path));
            touch($path);
        }

        $this->actingAs(admin())
            ->get(route('device.vm.show', ['device' => $device, 'vminfo' => $vm]))
            ->assertOk()
            ->assertSee('web')
            ->assertSee('All virtual machines')
            ->assertSee('vminfo_cpu')
            ->assertSee('vminfo_net')
            ->assertDontSee('vminfo_mem') // not recorded
            ->assertDontSee('No graphs have been recorded');
    } finally {
        File::deleteDirectory($rrdDir);
    }
});

test('graph page shows the vm as text', function () {
    $vm = Vminfo::factory()->create(['vm_type' => 'proxmox', 'vmwVmDisplayName' => 'web']);

    $this->actingAs(admin())
        ->get("/graphs?type=vminfo_cpu&id=$vm->id")
        ->assertOk()
        ->assertSee(':: VM :: web')
        ->assertDontSee('&lt;a', false);
});

test('vm without graphs', function () {
    $vm = Vminfo::factory()->create(['vm_type' => 'vmware']);
    LibrenmsConfig::set('rrd_dir', sys_get_temp_dir() . '/librenms-vm-test-' . uniqid());
    LibrenmsConfig::set('rrdcached', '');

    $this->actingAs(admin())
        ->get(route('device.vm.show', ['device' => $vm->device_id, 'vminfo' => $vm]))
        ->assertOk()
        ->assertSee('No graphs have been recorded')
        ->assertDontSee('vminfo_cpu');
});

test('vm of another device is not found', function () {
    $device = Device::factory()->create();
    $vm = Vminfo::factory()->create();

    $this->actingAs(admin())
        ->get(route('device.vm.show', ['device' => $device, 'vminfo' => $vm]))
        ->assertNotFound();
});

test('user without access is forbidden', function () {
    $vm = Vminfo::factory()->create();
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('device.vm.show', ['device' => $vm->device_id, 'vminfo' => $vm]))
        ->assertForbidden();
});

test('device viewer without vm access cannot see the vm graphs', function () {
    $vm = Vminfo::factory()->create(['vm_type' => 'proxmox']);
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo(Permission::findOrCreate('device.viewAll'));

    $this->actingAs($user)
        ->get(route('device', ['device' => $vm->device_id, 'tab' => 'vm', 'graph' => 'cpu']))
        ->assertForbidden();
});

test('table links to the vm graphs', function () {
    $vm = Vminfo::factory()->create();

    $response = $this->actingAs(admin())->postJson(route('table.vminfo'), [
        'device_id' => $vm->device_id,
        'current' => 1,
        'rowCount' => 50,
        'searchPhrase' => '',
    ]);

    $response->assertOk();
    $this->assertStringContainsString(
        route('device.vm.show', ['device' => $vm->device_id, 'vminfo' => $vm->id]),
        $response->json('rows.0.graphs')
    );
});

test('all vms graph view shows the vms that recorded the graph', function () {
    $recorded = Vminfo::factory()->create(['vm_type' => 'proxmox', 'vmwVmVMID' => 100]);
    $container = Vminfo::factory()->create(['vm_type' => 'proxmox-lxc']);
    $unrecorded = Vminfo::factory()->create(['vm_type' => 'vmware']);
    $rrdDir = useTemporaryRrdDir();

    try {
        recordMetric($recorded, 'vm.cpu');

        $this->actingAs(admin())
            ->get(route('vminfo.index', ['graph' => 'cpu']))
            ->assertOk()
            ->assertSee('vminfo_cpu')
            ->assertSee(route('device.vm.show', ['device' => $recorded->device_id, 'vminfo' => $recorded->id]))
            ->assertSee(route('device.vm.show', ['device' => $container->device_id, 'vminfo' => $container->id]))
            ->assertDontSee(route('device.vm.show', ['device' => $unrecorded->device_id, 'vminfo' => $unrecorded->id]))
            ->assertDontSee('data-url="' . route('table.vminfo') . '"', false);
    } finally {
        File::deleteDirectory($rrdDir);
    }
});

test('device graph view only shows the vms of that device', function () {
    $device = Device::factory()->create();
    $local = Vminfo::factory()->for($device)->create(['vm_type' => 'proxmox', 'vmwVmVMID' => 100]);
    $other = Vminfo::factory()->create(['vm_type' => 'proxmox', 'vmwVmVMID' => 101]);
    $rrdDir = useTemporaryRrdDir();

    try {
        recordMetric($local, 'vm.net');
        recordMetric($other, 'vm.net');

        $this->actingAs(admin())
            ->get(route('device', ['device' => $device, 'tab' => 'vm', 'graph' => 'net']))
            ->assertOk()
            ->assertSee('vminfo_net')
            ->assertSee(route('device.vm.show', ['device' => $device, 'vminfo' => $local->id]))
            ->assertDontSee(route('device.vm.show', ['device' => $other->device_id, 'vminfo' => $other->id]));
    } finally {
        File::deleteDirectory($rrdDir);
    }
});

test('vms of sources without metrics do not take up the graph page', function () {
    Vminfo::factory()->count(VminfoGraphs::PER_PAGE_OPTIONS[0])->create(['vm_type' => 'vmware', 'vmwVmDisplayName' => 'a-vmware']);
    $recorded = Vminfo::factory()->create(['vm_type' => 'proxmox', 'vmwVmVMID' => 100, 'vmwVmDisplayName' => 'z-proxmox']);

    $this->actingAs(admin())
        ->get(route('vminfo.index', ['graph' => 'cpu']))
        ->assertOk()
        ->assertSee(route('device.vm.show', ['device' => $recorded->device_id, 'vminfo' => $recorded->id]))
        ->assertDontSee('No virtual machines with graphs found.');
});

test('lxc containers do not take up the disk operations graph page', function () {
    $containers = Vminfo::factory()->count(VminfoGraphs::PER_PAGE_OPTIONS[0])->create(['vm_type' => 'proxmox-lxc', 'vmwVmDisplayName' => 'a-container']);
    $vm = Vminfo::factory()->proxmox()->create(['vmwVmDisplayName' => 'z-vm']);

    $this->actingAs(admin())
        ->get(route('vminfo.index', ['graph' => 'diskio_ops']))
        ->assertOk()
        ->assertSee(route('device.vm.show', ['device' => $vm->device_id, 'vminfo' => $vm->id]))
        ->assertDontSee(route('device.vm.show', ['device' => $containers->first()->device_id, 'vminfo' => $containers->first()->id]))
        ->assertViewHas('vms', fn ($vms): bool => $vms->total() === 1);
});

test('device disk operations graphs exclude lxc containers', function () {
    $device = Device::factory()->create();
    $vm = Vminfo::factory()->for($device)->proxmox()->create();
    $container = Vminfo::factory()->for($device)->create(['vm_type' => 'proxmox-lxc']);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'vm', 'graph' => 'diskio_ops']))
        ->assertOk()
        ->assertSee(route('device.vm.show', ['device' => $device, 'vminfo' => $vm->id]))
        ->assertDontSee(route('device.vm.show', ['device' => $device, 'vminfo' => $container->id]));
});

test('list view links to each graph view keeping the filter', function () {
    $filter = ['vm_type' => ['eq' => 'proxmox']];

    $this->actingAs(admin())
        ->get(route('vminfo.index', ['filter' => $filter]))
        ->assertOk()
        ->assertSee('data-url="' . route('table.vminfo') . '"', false)
        ->assertSee(route('vminfo.index', ['graph' => 'mem', 'filter' => $filter]))
        ->assertSee(route('vminfo.index', ['graph' => 'diskio_ops', 'filter' => $filter]));
});

test('unknown graph is rejected', function () {
    $this->actingAs(admin())
        ->get(route('vminfo.index', ['graph' => 'bogus']))
        ->assertSessionHasErrors('graph');
});

function useTemporaryRrdDir(): string
{
    $rrdDir = sys_get_temp_dir() . '/librenms-vm-test-' . uniqid();
    LibrenmsConfig::set('rrd_dir', $rrdDir);
    LibrenmsConfig::set('rrdcached', '');

    return $rrdDir;
}

function recordMetric(Vminfo $vm, string $metric): void
{
    $path = Rrd::metricName($vm->metricIdentity($metric))->fullPath();
    File::ensureDirectoryExists(dirname($path));
    touch($path);
}

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}