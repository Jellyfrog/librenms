<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Secret;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Data\Source\Snmp\RawSnmpResponse;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Permission::findOrCreate('device.create');
    $this->admin = User::factory()->admin()->create(['enabled' => 1]);
});

test('new secret data is ajson object without defaults', function () {
    // Alpine sets the fields by name, those are lost when a JSON array is posted
    $this->actingAs($this->admin)->get(route('device.add'))
        ->assertOk()
        ->assertViewHas('add_device_config', fn (array $config): bool => json_encode($config['methods']['proxmox-api']['formData']) === '{}'
            && json_encode($config['methods']['ipmi']['formData']) === '{}'
            && json_encode($config['methods']['snmp']['formData']) !== '{}');
});

test('proxmox token secret must be auuid', function () {
    $this->actingAs($this->admin)->postJson(route('device.add.store'), [
        'hostname' => 'pve.example.com',
        'polling_methods' => ['proxmox-api' => [
            'active' => 1,
            'secret_mode' => 'new',
            'secret_data' => ['token_id' => 'librenms@pve!monitoring', 'token_secret' => 'Not-A-Token!'],
        ]],
    ])->assertJsonValidationErrors('polling_methods.proxmox-api.secret_data.token_secret');

    expect(Validator::make(
        ['token_id' => 'librenms@pve!monitoring', 'token_secret' => '5b1d6f3e-7c2a-4e8b-9f10-2a3b4c5d6e7f'],
        SecretType::ProxmoxApi->definition()->rules(),
    )->passes())->toBeTrue();
});

test('index provides default display template', function () {
    LibrenmsConfig::set('device_display_default', '{{ $hostname }} - {{ $sysName }}');

    $this->actingAs($this->admin)->get(route('device.add'))
        ->assertOk()
        ->assertViewHas('default_display_template', '{{ $hostname }} - {{ $sysName }}')
        ->assertSee('name="display_template"', false)
        ->assertSee('id="secret-select-snmp"', false)
        ->assertSee('name="polling_methods[snmp][secret_id]"', false)
        ->assertSee('id="os-select"', false)
        ->assertSee(__('poller.validation_failed'));
    // add anyway dialog
});

test('index prefills hostname from search', function () {
    $this->actingAs($this->admin)->get(route('device.add', ['hostname' => '198.51.100.30']))
        ->assertOk()
        ->assertViewHas('add_device_config', fn (array $config): bool => $config['hostname'] === '198.51.100.30');

    $this->withSession(['_old_input' => ['hostname' => 'corrected.example.test']])
        ->get(route('device.add', ['hostname' => '198.51.100.30']))
        ->assertViewHas('add_device_config', fn (array $config): bool => $config['hostname'] === 'corrected.example.test');
});

test('index default methods follow icmp check', function () {
    $this->actingAs($this->admin)->get(route('device.add'))
        ->assertViewHas('oldActiveMethods', [PollingMethodType::Icmp->value, PollingMethodType::Snmp->value]);

    LibrenmsConfig::set('icmp_check', false);
    $this->actingAs($this->admin)->get(route('device.add'))
        ->assertViewHas('oldActiveMethods', [PollingMethodType::Snmp->value]);
});

test('store without checking saves device and settings', function () {
    [$default] = defaultSecrets('public');
    snmpBackend()->shouldNotReceive('get');

    $response = store('unchecked.example.com', [
        'snmp' => [
            'validate' => '0',
            'settings' => ['transport' => 'udp', 'port' => '', 'port_association_mode' => 'ifName'],
        ],
    ], ['display_template' => '{{ $hostname }} ({{ $ip }})']);

    $device = Device::where('hostname', 'unchecked.example.com')->firstOrFail();
    $response->assertOk()->assertJson(['status' => 'ok', 'redirect' => route('device', ['device' => $device->device_id])]);
    expect($device->display_template)->toBe('{{ $hostname }} ({{ $ip }})');

    $snmp = $device->pollingMethod(PollingMethodType::Snmp);
    expect($snmp)->not->toBeNull();
    expect($snmp->settings)->toBe(['transport' => 'udp', 'port_association_mode' => 'ifName']);
    // empty is left to the default
    expect($snmp->secret_id)->toBe($default->id);
    expect($snmp->last_check_successful)->toBeNull();
});

test('store without checking or credentials is rejected', function () {
    LibrenmsConfig::set('snmp.default_credentials', []);

    store('no-credentials.example.com', ['snmp' => ['validate' => '0']])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('exceptions.missing_secret', ['method' => 'SNMP']));

    $this->assertDatabaseMissing('devices', ['hostname' => 'no-credentials.example.com']);
});

test('store unreachable returns each credential tried', function () {
    defaultSecrets('public', 'private');
    snmpBackend()->shouldReceive('get')->andReturn(new RawSnmpResponse('', 'Timeout', 1));

    $response = store('unreachable.example.com', ['snmp' => ['validate' => '1']]);

    $reasons = [
        'SNMP v2c: No reply using credential "Default public"',
        'SNMP v2c: No reply using credential "Default private"',
        'SNMP v1: No reply using credential "Default public"',
        'SNMP v1: No reply using credential "Default private"',
    ];
    $response->assertStatus(422)
        ->assertJsonPath('status', 'unreachable')
        ->assertJsonPath('error_details', implode("\n", $reasons))
        ->assertJsonPath('errors.hostname.1', $reasons[0])
        ->assertJsonPath('errors.hostname.2', $reasons[1]);
    $this->assertStringContainsString('unreachable.example.com', $response->json('errors.hostname.0'));
    $this->assertDatabaseMissing('devices', ['hostname' => 'unreachable.example.com']);
});

test('store with existing secret only tries that secret', function () {
    defaultSecrets('global-community');
    $secret = Secret::factory()->create([
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'target-community'],
    ]);

    $tried = [];
    snmpBackend()->shouldReceive('get')->andReturnUsing(function ($target, $oids, $config) use (&$tried) {
        $tried[] = "$config->version $config->community";

        return new RawSnmpResponse('', 'Timeout', 1);
    });

    store('existing-secret.example.com', [
        'snmp' => ['validate' => '1', 'secret_mode' => 'existing', 'secret_id' => $secret->id],
    ])->assertStatus(422);

    expect($tried)->toBe(['v2c target-community', 'v1 target-community']);
    // without a version a community is tried as v1 too
});

test('store without checking still rejects duplicate ip', function () {
    [$default] = defaultSecrets('public');
    Device::factory()->create(['hostname' => 'existing.example.com', 'ip' => '192.0.2.10']);
    snmpBackend()->shouldNotReceive('get');
    fping()->shouldNotReceive('ping');

    store('192.0.2.10', [
        'icmp' => ['validate' => '0'],
        'snmp' => ['validate' => '0'],
    ])->assertStatus(422)->assertJsonPath('status', 'duplicate');

    $this->assertDatabaseMissing('devices', ['hostname' => '192.0.2.10']);

    // a unique IP is added without checking the methods
    store('192.0.2.11', [
        'icmp' => ['validate' => '0'],
        'snmp' => ['validate' => '0'],
    ])->assertOk();

    $snmp = Device::where('hostname', '192.0.2.11')->firstOrFail()->pollingMethod(PollingMethodType::Snmp);
    expect($snmp)->not->toBeNull();
    expect($snmp->secret_id)->toBe($default->id);
    expect($snmp->last_check_successful)->toBeNull();
});

test('duplicate can be added anyway', function () {
    LibrenmsConfig::set('allow_duplicate_sysName', false);
    Device::factory()->create(['hostname' => 'existing.example.com', 'sysName' => 'router1']);
    fping()->shouldReceive('ping')->andReturn(FpingResponse::artificialUp());

    $methods = ['icmp' => ['validate' => '1']];
    store('ping.example.com', $methods, ['sysName' => 'router1'])
        ->assertStatus(422)
        ->assertJsonPath('status', 'duplicate');

    // the duplicate checks are what add anyway (force_add) skips
    store('ping.example.com', $methods, ['sysName' => 'router1', 'force_add' => 1])->assertOk();
    $this->assertDatabaseHas('devices', ['hostname' => 'ping.example.com', 'sysName' => 'router1']);
});

test('duplicate hostname can not be added anyway', function () {
    Device::factory()->create(['hostname' => 'existing.example.com']);

    $response = store('existing.example.com', ['icmp' => ['validate' => '0']], ['force_add' => 1])
        ->assertStatus(422);
    expect($response->json('status'))->toBeNull();
});

test('force add skips all checks', function () {
    defaultSecrets('public');
    snmpBackend()->shouldNotReceive('get');
    fping()->shouldNotReceive('ping');

    store('forced.example.com', [
        'icmp' => ['validate' => '1'],
        'snmp' => ['validate' => '1'],
    ], ['force_add' => 1])->assertOk();

    $this->assertDatabaseHas('devices', ['hostname' => 'forced.example.com']);
});

test('only methods with validate checked are checked', function () {
    defaultSecrets('public');
    snmpBackend()->shouldNotReceive('get');
    fping()->shouldReceive('ping')->once()->andReturn(FpingResponse::artificialUp('192.0.2.1'));

    store('partial.example.com', [
        'icmp' => ['validate' => '1'],
        'snmp' => ['validate' => '0'],
    ])->assertOk();

    $device = Device::where('hostname', 'partial.example.com')->firstOrFail();
    expect($device->pollingMethod(PollingMethodType::Icmp)?->last_check_successful)->toBeTrue();
    expect($device->pollingMethod(PollingMethodType::Snmp)?->last_check_successful)->toBeNull();
});

test('new secret requires secret create permission', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->givePermissionTo('device.create');

    $this->actingAs($user)->postJson(route('device.add.store'), [
        'hostname' => 'no-secret-create.example.com',
        'polling_methods' => ['snmp' => [
            'active' => '1',
            'validate' => '0',
            'secret_mode' => 'new',
            'description' => 'Not Allowed',
            'secret_type' => 'snmp-community', 'secret_data' => ['community' => 'public'],
        ]],
    ])->assertForbidden();

    $this->assertDatabaseMissing('secrets', ['description' => 'Not Allowed']);
    $this->assertDatabaseMissing('devices', ['hostname' => 'no-secret-create.example.com']);
});

test('new secret gets unique default description', function () {
    Secret::factory()->create(['description' => 'SNMP 127.0.0.10', 'secret_type' => SecretType::SnmpCommunity]);

    store('127.0.0.10', ['snmp' => [
        'validate' => '0',
        'secret_mode' => 'new',
        'description' => '',
        'secret_type' => 'snmp-community', 'secret_data' => ['community' => 'y'],
    ]])->assertOk();

    $this->assertDatabaseHas('secrets', ['description' => 'SNMP 127.0.0.10 (2)']);
});

test('secret of another type is an error of the method', function () {
    $ipmi = Secret::factory()->create(['description' => 'add ipmi', 'secret_type' => SecretType::Ipmi, 'data' => ['username' => 'admin']]);

    // the error is for the method, so the form shows it on its tab
    store('wrong-type.example.com', ['snmp' => [
        'validate' => '0',
        'secret_mode' => 'existing',
        'secret_id' => $ipmi->id,
    ]])->assertUnprocessable()->assertJsonValidationErrors(['polling_methods.snmp.secret_id']);

    $this->assertDatabaseMissing('devices', ['hostname' => 'wrong-type.example.com']);
});

test('index shows the community version for communities only', function () {
    $community = Secret::factory()->create(['description' => 'add community', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'public']]);
    $response = $this->actingAs($this->admin)->get(route('device.add'))->assertOk();
    $snmp = collect($response->viewData('availableMethods'))->firstWhere('type', 'snmp');

    $field = collect($snmp['settings_fields'])->firstWhere('key', 'version');
    $this->assertStringContainsString("secretTypes['snmp']", $field['visible_if_expression']);
    expect(array_column($snmp['secret_types'], 'type'))->toBe(['snmp-community', 'snmp-v3']);
    expect($response->viewData('add_device_config')['secret_type_by_id']['snmp']->{$community->id})->toBe('snmp-community');
});

test('store requires apolling method', function () {
    $this->actingAs($this->admin)->postJson(route('device.add.store'), [
        'hostname' => 'no-methods.example.com',
        'polling_methods' => [],
    ])->assertStatus(422)->assertJsonValidationErrors(['polling_methods' => 'At least one polling method is required']);

    $this->actingAs($this->admin)->postJson(route('device.add.store'), [
        'hostname' => 'inactive-methods.example.com',
        'polling_methods' => ['snmp' => ['active' => 0]],
    ])->assertStatus(422)->assertJsonStructure(['errors' => ['polling_methods']]);

    $this->assertDatabaseMissing('devices', ['hostname' => 'inactive-methods.example.com']);
});

test('store rejects long display template', function () {
    store('display-test.example.com', ['snmp' => ['validate' => '0']], ['display_template' => str_repeat('a', 129)])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['display_template']);
});

test('store does not leak unexpected exception messages', function () {
    fping()->shouldReceive('ping')->andThrow(new \RuntimeException('SQLSTATE[23000] secret details'));

    $response = store('127.0.0.12', ['icmp' => ['validate' => '1']]);

    $response->assertStatus(422);
    $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
});

/**
 * @param  array<string, array<string, mixed>>  $methods  method type => form data, all active
 * @param  array<string, mixed>  $extra
 * @return TestResponse<JsonResponse>
 */
function store(string $hostname, array $methods, array $extra = []): TestResponse
{
    return $this->actingAs($this->admin)->postJson(route('device.add.store'), [
        'hostname' => $hostname,
        'poller_group' => 0,
        'polling_methods' => array_map(fn (array $data) => ['active' => '1', 'secret_mode' => 'default', ...$data], $methods),
        ...$extra,
    ]);
}

/**
 * Create SNMP v2c default credentials, in order.
 *
 * @return Secret[]
 */
function defaultSecrets(string ...$communities): array
{
    $secrets = array_map(fn (string $community) => Secret::factory()->create([
        'description' => "Default $community",
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => $community],
    ]), $communities);
    LibrenmsConfig::set('snmp.default_credentials', array_map(fn (Secret $secret) => $secret->id, $secrets));

    return $secrets;
}

function snmpBackend(): MockInterface
{
    $backend = Mockery::mock(SnmpBackendInterface::class);
    $this->app->instance(SnmpBackendInterface::class, $backend);

    return $backend;
}

function fping(): MockInterface
{
    $fping = Mockery::mock(Fping::class);
    $this->app->instance(Fping::class, $fping);

    return $fping;
}