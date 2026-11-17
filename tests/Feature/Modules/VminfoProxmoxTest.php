<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Facades\DeviceCache;
use App\Facades\LibrenmsConfig;
use App\Facades\Rrd;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use App\Models\User;
use App\Models\Vminfo as VminfoModel;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\PowerState;
use LibreNMS\Enum\ProcessType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Interfaces\Data\WriteInterface;
use LibreNMS\Modules\Vminfo;
use LibreNMS\OS;
use LibreNMS\Util\ModuleList;

private const string API = 'https://pve.example.com:8006/api2/json/';

private const string CLUSTER = 'proxmox-abababababababababababababababab';

beforeEach(function () {
    config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);

    // secret data is encrypted
    $this->device = Device::factory()->create(['os' => 'generic', 'hostname' => 'pve.example.com']);
    $secret = Secret::factory()->create([
        'secret_type' => SecretType::ProxmoxApi,
        'data' => ['token_id' => 'librenms@pve!monitoring', 'token_secret' => 'abc123'],
    ]);
    $this->proxmoxMethod = DevicePollingMethod::factory()->create([
        'device_id' => $this->device->device_id,
        'method_type' => PollingMethodType::ProxmoxApi,
        'secret_id' => $secret->id,
        'affects_availability' => false,
        'settings' => [],
    ]);
    DeviceCache::setPrimary($this->device->device_id);
});

function fakeApi(): void
{
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'cluster/resources?type=vm' => clusterGuests([['vmid' => 100, 'name' => 'web', 'status' => 'running'], ['vmid' => 101, 'name' => 'db', 'status' => 'stopped'], ['vmid' => 200, 'status' => 'unknown']]),
        self::API . 'nodes/localhost/lxc' => Http::response(['data' => []]),
        self::API . 'nodes/localhost/qemu/100/agent/get-osinfo' => Http::response(['data' => ['result' => ['pretty-name' => 'Debian GNU/Linux 12 (bookworm)']]]),
        self::API . 'nodes/localhost/qemu?full=1' => Http::response(['data' => [
            [
                'vmid' => 100, 'name' => 'web', 'cpus' => 2, 'status' => 'running', 'cpu' => 0.25, 'mem' => 1073741824, 'maxmem' => 4294967296,
                'disk' => 0, 'maxdisk' => 34359738368, 'netin' => 1000, 'netout' => 2000,
                'blockstat' => [
                    'scsi0' => ['rd_bytes' => 100, 'wr_bytes' => 200, 'rd_operations' => 1, 'wr_operations' => 2],
                    'scsi1' => ['rd_bytes' => 10, 'wr_bytes' => 20, 'rd_operations' => 3, 'wr_operations' => 4],
                ],
            ],
            ['vmid' => 101, 'name' => 'db', 'maxmem' => 2147483648, 'cpus' => 1, 'status' => 'stopped'],
        ]]),
        self::API . 'nodes/localhost/qemu/100/agent/get-fsinfo' => Http::response(['data' => ['result' => [
            ['type' => 'ext4', 'used-bytes' => 500, 'total-bytes' => 1000],
            ['type' => 'tmpfs', 'used-bytes' => 5, 'total-bytes' => 10],
        ]]]),
    ]);
}

test('discover and poll', function () {
    fakeApi();
    $module = new Vminfo;
    $module->discover(os());

    $vms = $this->device->vminfo()->orderBy('vmwVmVMID')->get();
    expect($vms->pluck('vmwVmDisplayName')->all())->toBe(['web', 'db']);
    expect($vms[0]->vmwVmGuestOS)->toBe('Debian GNU/Linux 12 (bookworm)');
    expect($vms[1]->vmwVmGuestOS)->toBeNull();
    expect($vms[0]->vmwVmMemSize)->toEqual(4096);
    expect($vms[0]->vmwVmState)->toBe(PowerState::ON);
    expect($vms[1]->vmwVmState)->toBe(PowerState::OFF);
    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'PVEAPIToken=librenms@pve!monitoring=abc123'));

    $written = [];
    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldReceive('write')->andReturnUsing(function (string $measurement, array $fields, array $tags) use (&$written): void {
        $written[$measurement][$tags['id']] = $fields;
    });

    $module->poll(os(), $datastore);

    // stopped VMs are not polled
    expect(array_map(strval(...), array_keys($written['vm.cpu'])))->toBe(['100']);
    expect($written['vm.cpu'][100])->toEqual(['usage' => 25]);
    expect($written['vm.memory'][100])->toEqual(['used' => 1073741824, 'total' => 4294967296]);
    expect($written['vm.disk'][100])->toEqual(['used' => 500, 'total' => 1000]);
    // guest agent, pseudo filesystems skipped
    expect($written['vm.net'][100])->toEqual(['in' => 1000, 'out' => 2000]);
    expect($written['vm.diskio'][100]['read_bytes'])->toBe(110);
    expect($written['vm.diskio'][100])->toEqual(['read_bytes' => 110, 'write_bytes' => 220]);
    expect($written['vm.diskops'][100]['write_ops'])->toBe(6);
});

test('uses the proxmox polling method', function () {
    fakeApi();
    $module = new Vminfo;

    expect($module->shouldDiscover(os()))->toBeTrue();
    expect($module->shouldPoll(os()))->toBeTrue();

    // a VM may migrate here
    $module->discover(os());
    expect($module->shouldPoll(os()))->toBeTrue();

    // no Proxmox polling method
    $this->proxmoxMethod->delete();
    expect($module->shouldDiscover(os()))->toBeFalse();
    expect($module->shouldPoll(os()))->toBeFalse();
});

test('only syncs the vms of its type', function () {
    fakeApi();
    createVm('vmware', 5, 'esx-guest');
    // from another source
    createVm('proxmox', 999, 'removed');

    (new Vminfo)->discover(os());

    expect($this->device->vminfo()->get()->map->getCompositeKey()->all())->toEqualCanonicalizing(['-vmware-5', self::CLUSTER . '-proxmox-100', self::CLUSTER . '-proxmox-101']);
});

test('proxmox enables the vminfo module', function () {
    LibrenmsConfig::set('discovery_modules.vminfo', false);
    LibrenmsConfig::set('poller_modules.vminfo', false);
    $modules = new ModuleList;

    $status = $modules->moduleStatus(ProcessType::Discovery, 'vminfo', $this->device);
    expect($status->isEnabled())->toBeTrue();
    expect($status->reason())->toBe('by polling method');
    expect($modules->moduleStatus(ProcessType::Poller, 'vminfo', $this->device)->isEnabled())->toBeTrue();

    // the device setting overrides the polling method
    $this->device->setAttrib('discover_vminfo', false);
    expect($modules->moduleStatus(ProcessType::Discovery, 'vminfo', $this->device)->isEnabled())->toBeFalse();

    // a disabled method does not enable modules
    $this->proxmoxMethod->update(['enabled' => false]);
    $device = Device::find($this->device->device_id);
    expect($modules->moduleStatus(ProcessType::Poller, 'vminfo', $device)->isEnabled())->toBeFalse();
});

test('does not run when the api check fails', function () {
    Http::fake(['*' => Http::response(null, 401)]);
    createVm('proxmox', 100, 'web');

    $module = new Vminfo;

    expect($module->shouldDiscover(os()))->toBeFalse();
    expect($module->shouldPoll(os()))->toBeFalse();
});

test('keeps vms when the vm list fails', function () {
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'cluster/resources?type=vm' => Http::response(null, 403),
    ]);
    createVm('proxmox', 100, 'web');

    (new Vminfo)->discover(os());

    expect($this->device->vminfo()->pluck('vmwVmDisplayName')->all())->toBe(['web']);
    expect($this->device->eventlogs()->where('type', 'vminfo')->count())->toBe(1);
});

test('skips vms removed since discovery', function () {
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'nodes/localhost/qemu?full=1' => Http::response(['data' => [['vmid' => 101, 'status' => 'running', 'cpu' => 0.5]]]),
        self::API . 'nodes/localhost/lxc' => Http::response(['data' => []]),
        self::API . 'nodes/localhost/qemu/101/agent/get-fsinfo' => Http::response(null, 500),
    ]);
    createVm('proxmox', 100, 'web');
    createVm('proxmox', 101, 'db');

    $written = [];
    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldReceive('write')->andReturnUsing(function (string $measurement, array $fields, array $tags) use (&$written): void {
        $written[$measurement][$tags['id']] = $fields;
    });

    (new Vminfo)->poll(os(), $datastore);

    expect(array_keys($written['vm.cpu']))->toBe([101]);
});

test('poll only makes one status request and asks running vm agents', function () {
    fakeApi();
    createVm('proxmox', 100, 'web');
    createVm('proxmox', 101, 'db');
    $datastore = Mockery::mock(WriteInterface::class)->shouldIgnoreMissing();

    (new Vminfo)->poll(os(), $datastore);

    Http::assertSentCount(5);
    // the check and cluster, the VM and container lists and the agent of the running VM
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/status/current'));
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'qemu/101/agent'));
});

test('agent requests use the client options', function () {
    $sent = [];
    Http::fake(function (Request $request, array $options) use (&$sent) {
        if (str_contains($request->url(), '/agent/')) {
            $sent[] = ['auth' => $request->header('Authorization')[0] ?? null, 'verify' => $options['verify'] ?? null, 'timeout' => $options['timeout'] ?? null, 'hasProxy' => isset($options['proxy'])];
        }

        if (str_ends_with($request->url(), '/version')) {
            return Http::response(['data' => ['version' => '8.2.4']]);
        }

        return str_ends_with($request->url(), '/certificates/info')
            ? rootCa()
            : Http::response(['data' => [['vmid' => 100, 'type' => 'qemu', 'name' => 'web', 'maxmem' => 1048576, 'cpus' => 1, 'status' => 'running']]]);
    });

    (new Vminfo)->discover(os());

    expect($sent)->toBe([['auth' => 'PVEAPIToken=librenms@pve!monitoring=abc123', 'verify' => true, 'timeout' => 10, 'hasProxy' => true]]);
});

test('writes to the file the graphs read', function () {
    fakeApi();
    createVm('proxmox', 100, 'web');
    $vm = $this->device->vminfo()->first();

    $files = [];
    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldReceive('write')->andReturnUsing(function (string $measurement, array $fields, array $tags, array $meta) use (&$files): void {
        $files[$measurement] = Rrd::metricName($meta['metric'])->relativePath();
    });

    (new Vminfo)->poll(os(), $datastore);

    expect($files)->toHaveCount(6);
    foreach ($files as $measurement => $file) {
        expect($file)->toBe(Rrd::name(self::CLUSTER, "$measurement-proxmox-100")->relativePath());
        expect(Rrd::metricName($vm->metricIdentity($measurement))->relativePath())->toBe($file);
    }
});

function fakeContainers(): void
{
    $containers = [
        [
            'vmid' => 126, 'name' => 'test', 'type' => 'lxc', 'status' => 'running', 'cpus' => 1, 'cpu' => 0.5,
            'mem' => 2310144, 'maxmem' => 536870912, 'disk' => 13864960, 'maxdisk' => 8350298112,
            'netin' => 218508, 'netout' => 866, 'diskread' => 5128192, 'diskwrite' => 16384,
        ],
        ['vmid' => 127, 'name' => 'off', 'type' => 'lxc', 'status' => 'stopped', 'cpus' => 2, 'maxmem' => 1073741824],
    ];

    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '9.2.24']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'nodes/localhost/qemu?full=1' => Http::response(['data' => [
            ['vmid' => 100, 'name' => 'web', 'maxmem' => 4294967296, 'cpus' => 2, 'status' => 'stopped'],
        ]]),
        self::API . 'cluster/resources?type=vm' => clusterGuests([['vmid' => 100, 'name' => 'web', 'maxmem' => 4294967296, 'cpus' => 2, 'status' => 'stopped']], $containers),
        self::API . 'nodes/localhost/lxc' => Http::response(['data' => $containers]),
        self::API . 'nodes/localhost/lxc/126/config' => Http::response(['data' => ['ostype' => 'alpine']]),
        self::API . 'nodes/localhost/lxc/127/config' => Http::response(null, 500),
    ]);
}

test('discovers containers', function () {
    fakeContainers();

    (new Vminfo)->discover(os());

    $containers = $this->device->vminfo()->where('vm_type', 'proxmox-lxc')->orderBy('vmwVmVMID')->get();
    expect($containers->pluck('vmwVmDisplayName')->all())->toBe(['test', 'off']);
    expect($containers->pluck('vmwVmGuestOS')->all())->toBe(['alpine', null]);
    expect($containers[0]->vmwVmMemSize)->toEqual(512);
    expect($containers[0]->vmwVmState)->toBe(PowerState::ON);
    expect($containers[1]->vmwVmState)->toBe(PowerState::OFF);
    expect($this->device->vminfo()->where('vm_type', 'proxmox')->count())->toBe(1);
});

test('polls containers without the agent', function () {
    fakeContainers();
    createVm('proxmox-lxc', 126, 'test');
    createVm('proxmox-lxc', 127, 'off');

    $written = [];
    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldReceive('write')->andReturnUsing(function (string $measurement, array $fields, array $tags) use (&$written): void {
        $written[$measurement][$tags['id']] = $fields;
    });

    (new Vminfo)->poll(os(), $datastore);

    expect(array_keys($written['vm.cpu']))->toBe([126]);
    expect($written['vm.cpu'][126])->toEqual(['usage' => 50]);
    expect($written['vm.memory'][126])->toEqual(['used' => 2310144, 'total' => 536870912]);
    expect($written['vm.disk'][126])->toEqual(['used' => 13864960, 'total' => 8350298112]);
    expect($written['vm.diskio'][126])->toEqual(['read_bytes' => 5128192, 'write_bytes' => 16384]);
    expect($written['vm.net'][126])->toEqual(['in' => 218508, 'out' => 866]);
    $this->assertArrayNotHasKey('vm.diskops', $written);
    expect($this->device->vminfo()->where('vmwVmVMID', 127)->first()->vmwVmState)->toBe(PowerState::OFF);
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/agent/'));
});

test('keeps the guest os when it can not be read', function () {
    fakeApi();
    createVm('proxmox', 101, 'db');
    $this->device->vminfo()->where('vmwVmVMID', 101)->update(['vmwVmGuestOS' => 'Arch Linux']);

    (new Vminfo)->discover(os());

    expect($this->device->vminfo()->where('vmwVmVMID', 101)->first()->vmwVmGuestOS)->toBe('Arch Linux');
});

test('paused vm is suspended and has no metrics or agent calls', function () {
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'nodes/localhost/qemu?full=1' => Http::response(['data' => [
            ['vmid' => 100, 'name' => 'web', 'maxmem' => 1048576, 'cpus' => 1, 'status' => 'running', 'qmpstatus' => 'paused', 'cpu' => 0.5],
        ]]),
        self::API . 'cluster/resources?type=vm' => clusterGuests([['vmid' => 100, 'name' => 'web', 'status' => 'running']]),
        self::API . 'nodes/localhost/lxc' => Http::response(['data' => []]),
    ]);
    $module = new Vminfo;

    $module->discover(os());
    expect($this->device->vminfo()->first()->vmwVmState)->toBe(PowerState::SUSPENDED);

    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldNotReceive('write');
    $module->poll(os(), $datastore);

    expect($this->device->vminfo()->first()->vmwVmState)->toBe(PowerState::SUSPENDED);
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/agent/'));
});

test('templates are not discovered', function () {
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'nodes/localhost/qemu?full=1' => Http::response(['data' => [
            ['vmid' => 100, 'name' => 'web', 'maxmem' => 1048576, 'cpus' => 1, 'status' => 'stopped'],
            ['vmid' => 900, 'name' => 'vm-template', 'maxmem' => 1048576, 'cpus' => 1, 'status' => 'stopped', 'template' => 1],
        ]]),
        self::API . 'cluster/resources?type=vm' => clusterGuests([['vmid' => 100, 'name' => 'web', 'status' => 'stopped'], ['vmid' => 900, 'name' => 'vm-template', 'status' => 'stopped', 'template' => 1]], [['vmid' => 901, 'name' => 'ct-template', 'status' => 'stopped', 'template' => 1]]),
        self::API . 'nodes/localhost/lxc' => Http::response(['data' => [
            ['vmid' => 901, 'name' => 'ct-template', 'maxmem' => 1048576, 'cpus' => 1, 'status' => 'stopped', 'template' => 1],
        ]]),
    ]);

    (new Vminfo)->discover(os());

    expect($this->device->vminfo()->pluck('vmwVmDisplayName')->all())->toBe(['web']);
});

/**
 * @param  list<array<string, mixed>>  $filesystems
 * @return array<string, mixed>
 */
function pollVmDisk(array $filesystems): array
{
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'nodes/localhost/qemu?full=1' => Http::response(['data' => [['vmid' => 100, 'status' => 'running', 'disk' => 5, 'maxdisk' => 10]]]),
        self::API . 'nodes/localhost/lxc' => Http::response(['data' => []]),
        self::API . 'nodes/localhost/qemu/100/agent/get-fsinfo' => Http::response(['data' => ['result' => $filesystems]]),
    ]);
    createVm('proxmox', 100, 'web');

    $disk = [];
    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldReceive('write')->andReturnUsing(function (string $measurement, array $fields) use (&$disk): void {
        if ($measurement === 'vm.disk') {
            $disk = $fields;
        }
    });

    (new Vminfo)->poll(os(), $datastore);

    return $disk;
}

test('filesystems without sizes fall back to the host', function () {
    $disk = pollVmDisk([
        ['name' => 'vol1', 'mountpoint' => 'System Reserved', 'type' => 'NTFS'],
        ['name' => 'loop0', 'mountpoint' => '/snap/core', 'type' => 'squashfs', 'used-bytes' => 5, 'total-bytes' => 5],
    ]);

    expect($disk)->toEqual(['used' => 5, 'total' => 10]);
});

test('mounts of one device are counted once', function () {
    $disk = pollVmDisk([
        ['name' => 'sda1', 'mountpoint' => '/', 'type' => 'btrfs', 'used-bytes' => 100, 'total-bytes' => 1000],
        ['name' => 'sda1', 'mountpoint' => '/home', 'type' => 'btrfs', 'used-bytes' => 100, 'total-bytes' => 1000],
        ['name' => 'sdb1', 'mountpoint' => '/data', 'type' => 'ext4', 'used-bytes' => 20, 'total-bytes' => 200],
    ]);

    expect($disk)->toEqual(['used' => 120, 'total' => 1200]);
});

test('poll updates the power state', function () {
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'nodes/localhost/qemu?full=1' => Http::response(['data' => [['vmid' => 100, 'status' => 'stopped']]]),
        self::API . 'nodes/localhost/lxc' => Http::response(['data' => []]),
    ]);
    createVm('proxmox', 100, 'web');

    (new Vminfo)->poll(os(), Mockery::mock(WriteInterface::class));

    expect($this->device->vminfo()->first()->vmwVmState)->toBe(PowerState::OFF);
});

test('stuck guest agent does not fail the other vms', function () {
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'nodes/localhost/qemu?full=1' => Http::response(['data' => [
            ['vmid' => 100, 'name' => 'web', 'maxmem' => 4294967296, 'cpus' => 2, 'status' => 'running', 'disk' => 5, 'maxdisk' => 10],
        ]]),
        self::API . 'cluster/resources?type=vm' => clusterGuests([['vmid' => 100, 'name' => 'web', 'status' => 'running']]),
        self::API . 'nodes/localhost/lxc' => Http::response(['data' => []]),
        self::API . 'nodes/localhost/qemu/100/agent/*' => Http::failedConnection(),
    ]);
    $module = new Vminfo;

    $module->discover(os());
    expect($this->device->vminfo()->first()->vmwVmGuestOS)->toBeNull();

    $written = [];
    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldReceive('write')->andReturnUsing(function (string $measurement, array $fields, array $tags) use (&$written): void {
        $written[$measurement][$tags['id']] = $fields;
    });

    $module->poll(os(), $datastore);

    expect($written['vm.disk'][100])->toEqual(['used' => 5, 'total' => 10]);
    // hypervisor fallback
});

test('counters discard resets when avm restarts', function () {
    fakeApi();
    createVm('proxmox', 100, 'web');

    $definitions = [];
    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldReceive('write')->andReturnUsing(function (string $measurement, array $fields, array $tags, array $meta) use (&$definitions): void {
        $definitions[$measurement] = (string) $meta['rrd_def'];
    });

    (new Vminfo)->poll(os(), $datastore);

    foreach (['vm.net', 'vm.diskio', 'vm.diskops'] as $metric) {
        $this->assertStringContainsString(':DERIVE:', $definitions[$metric]);
        $this->assertStringNotContainsString(':COUNTER:', $definitions[$metric]);
    }
});

test('poll claims avm that migrated here', function () {
    fakeApi();
    $other = Device::factory()->create(['hostname' => 'pve2.example.com']);
    $vm = createVmOn($other, self::CLUSTER, 100);
    $metrics = [];
    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldReceive('write')->andReturnUsing(function (string $measurement, array $fields, array $tags, array $meta) use (&$metrics): void {
        $metrics[$measurement] = $meta['metric']->group();
    });

    (new Vminfo)->poll(os(), $datastore);

    expect($vm->refresh()->device_id)->toBe($this->device->device_id);
    // the same row
    expect($metrics['vm.cpu'])->toBe(self::CLUSTER);
    // the same files
    expect($this->device->eventlogs()->where('message', 'Virtual Machine migrated: migrated from pve2.example.com')->count())->toBe(1);
});

test('discovery claims avm that migrated here', function () {
    fakeApi();
    $vm = createVmOn(Device::factory()->create(), self::CLUSTER, 100);

    (new Vminfo)->discover(os());

    expect($vm->refresh()->device_id)->toBe($this->device->device_id);
    expect($vm->vmwVmDisplayName)->toBe('web');
    expect(VminfoModel::where('vmwVmVMID', 100)->count())->toBe(1);
});

test('does not claim the vm of another cluster', function () {
    fakeApi();
    $other = Device::factory()->create();
    $vm = createVmOn($other, 'proxmox-another', 100);

    (new Vminfo)->discover(os());

    expect($vm->refresh()->device_id)->toBe($other->device_id);
    expect(VminfoModel::where('vmwVmVMID', 100)->count())->toBe(2);
});

test('avm on another node is kept until that node claims it', function () {
    fakeApi();
    // the cluster also has VM 200, it is not on this node
    createVm('proxmox', 200, 'moved');

    (new Vminfo)->discover(os());

    $vm = $this->device->vminfo()->where('vmwVmVMID', 200)->first();
    expect($vm->vmwVmDisplayName)->toBe('moved');
    expect($vm->vmwVmState)->toBe(PowerState::UNKNOWN);
});

test('avm removed from the cluster is deleted from its node', function () {
    fakeApi();
    createVm('proxmox', 999, 'removed');

    (new Vminfo)->discover(os());

    expect(VminfoModel::where('vmwVmVMID', 999)->count())->toBe(0);
});

test('only deletes the vms of this device', function () {
    fakeApi();
    // a token may not see all VMs of the cluster
    createVmOn(Device::factory()->create(), self::CLUSTER, 999);

    (new Vminfo)->discover(os());

    expect(VminfoModel::where('vmwVmVMID', 999)->count())->toBe(1);
});

test('afailing source does not stop the others', function () {
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'cluster/resources?type=vm' => Http::response(null, 500),
    ]);
    $this->device->update(['os' => 'linux']);
    LibrenmsConfig::set('enable_libvirt', true);
    LibrenmsConfig::set('libvirt_protocols', []);
    // finds no VMs
    createVm('proxmox', 100, 'web');
    createVm('libvirt', 5, 'removed');

    (new Vminfo)->discover(os());

    expect($this->device->vminfo()->get()->map->getCompositeKey()->all())->toBe([self::CLUSTER . '-proxmox-100']);
});

test('afailed poll is logged instead of thrown', function () {
    Http::fake([
        self::API . 'version' => Http::response(['data' => ['version' => '8.2.4']]),
        self::API . 'nodes/localhost/certificates/info' => rootCa(),
        self::API . 'nodes/localhost/qemu?full=1' => Http::response(null, 500),
    ]);
    createVm('proxmox', 100, 'web');
    $datastore = Mockery::mock(WriteInterface::class);
    $datastore->shouldNotReceive('write');

    (new Vminfo)->poll(os(), $datastore);

    expect($this->device->eventlogs()->where('type', 'vminfo')->count())->toBe(1);
});

test('vms of snmp sources are kept when snmp is down and libvirt is enabled', function () {
    $this->proxmoxMethod->delete();
    $this->device->update(['os' => 'linux']);
    LibrenmsConfig::set('enable_libvirt', true);
    LibrenmsConfig::set('libvirt_protocols', []);
    // finds no VMs
    createVm('vmware', 5, 'esx-guest');
    createVm('xcp-ng', 6, 'xcp-guest');
    createVm('libvirt', 7, 'removed');

    (new Vminfo)->discover(os());

    expect($this->device->vminfo()->get()->map->getCompositeKey()->all())->toEqualCanonicalizing(['-vmware-5', '-xcp-ng-6']);
});

test('removing the method deletes its vms', function () {
    createVm('proxmox', 100, 'web');
    createVm('proxmox-lxc', 101, 'ct');
    createVm('vmware', 5, 'esx-guest');

    // from another source
    $this->actingAs(User::factory()->admin()->create(['enabled' => 1]))
        ->deleteJson(route('device.edit.polling.destroy', ['device' => $this->device, 'methodType' => 'proxmox-api']))
        ->assertOk();

    expect($this->device->vminfo()->get()->map->getCompositeKey()->all())->toBe(['-vmware-5']);
});

test('disabling the method keeps its vms', function () {
    $this->proxmoxMethod->update(['enabled' => false]);
    createVm('proxmox', 100, 'web');

    $module = new Vminfo;
    expect($module->shouldDiscover(os()))->toBeFalse();

    expect($this->device->vminfo()->count())->toBe(1);
});

test('disabling libvirt removes its vms', function () {
    $this->proxmoxMethod->delete();
    $this->device->update(['os' => 'linux']);
    LibrenmsConfig::set('enable_libvirt', false);
    createVm('libvirt', 5, 'removed');
    $module = new Vminfo;

    expect($module->shouldDiscover(os()))->toBeTrue();
    $module->discover(os());

    expect($this->device->vminfo()->count())->toBe(0);
    expect($module->shouldDiscover(os()))->toBeFalse();
});

test('libvirt does not need snmp', function () {
    $this->proxmoxMethod->delete();
    $this->device->update(['os' => 'linux']);
    $module = new Vminfo;

    LibrenmsConfig::set('enable_libvirt', false);
    expect($module->shouldDiscover(os()))->toBeFalse();

    LibrenmsConfig::set('enable_libvirt', true);
    expect($module->shouldDiscover(os()))->toBeTrue();
});

function rootCa(): PromiseInterface
{
    return Http::response(['data' => [
        ['filename' => 'pve-ssl.pem', 'fingerprint' => 'CD:CD'],
        ['filename' => 'pve-root-ca.pem', 'fingerprint' => implode(':', array_fill(0, 32, 'AB'))],
    ]]);
}

/**
 * The guest list of the cluster.
 *
 * @param  list<array<string, mixed>>  $vms
 * @param  list<array<string, mixed>>  $containers
 */
function clusterGuests(array $vms, array $containers = []): PromiseInterface
{
    return Http::response(['data' => [
        ...array_map(fn (array $vm): array => ['type' => 'qemu'] + $vm, $vms),
        ...array_map(fn (array $container): array => ['type' => 'lxc'] + $container, $containers),
    ]]);
}

function createVmOn(Device $device, string $cluster, int $vmId): VminfoModel
{
    return $device->vminfo()->create([
        'cluster_id' => $cluster,
        'vm_type' => 'proxmox',
        'vmwVmVMID' => $vmId,
        'vmwVmDisplayName' => 'migrated',
        'vmwVmGuestOS' => '',
        'vmwVmMemSize' => 1024,
        'vmwVmCpus' => 1,
        'vmwVmState' => PowerState::ON,
    ]);
}

function createVm(string $type, int $vmId, string $name): void
{
    $this->device->vminfo()->create([
        'cluster_id' => str_starts_with($type, 'proxmox') ? self::CLUSTER : null,
        'vm_type' => $type,
        'vmwVmVMID' => $vmId,
        'vmwVmDisplayName' => $name,
        'vmwVmGuestOS' => '',
        'vmwVmMemSize' => 1024,
        'vmwVmCpus' => 1,
        'vmwVmState' => PowerState::ON,
    ]);
}

function os(): OS
{
    // simulate a fresh discovery/poller run
    $device = DeviceCache::refresh($this->device->device_id)->toArray();

    return OS::make($device);
}