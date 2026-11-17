<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use Illuminate\Support\Facades\DB;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('snmp secrets are split by type', function () {
    $v2c = oldSecret('split v2c', ['version' => 'v2c', 'community' => 'split-test']);
    $v1 = oldSecret('split v1', ['version' => 'v1', 'community' => 'split-test']);
    $v3 = oldSecret('split v3', ['version' => 'v3', 'authlevel' => 'authNoPriv', 'authname' => 'user', 'authpass' => 'password1', 'authalgo' => 'SHA']);
    $v3NoName = oldSecret('split v3 no name', ['version' => 'v3', 'authlevel' => 'authPriv', 'authname' => '', 'authpass' => 'password2', 'cryptopass' => 'crypt2']);
    $versionOnly = oldSecret('split version only', ['version' => 'v1']);
    $ipmi = Secret::create(['description' => 'split ipmi', 'secret_type' => SecretType::Ipmi, 'data' => ['username' => 'admin']]);

    $v2cMethod = snmpMethod($v2c);
    $v1Method = snmpMethod($v1, ['port' => 1161]);
    $v3Method = snmpMethod($v3);
    $v3NoNameMethod = snmpMethod($v3NoName);
    $versionOnlyMethod = snmpMethod($versionOnly);

    $otherDefault = Secret::create(['description' => 'split other default', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'other']]);
    DB::table('config')->updateOrInsert(['config_name' => 'snmp.default_credentials'], ['config_value' => json_encode([$v1, $otherDefault->id, $v2c, $versionOnly, $v3])]);

    migration()->up();

    // the v1 and v2c secrets have the same community, the first one is kept
    $community = Secret::findOrFail($v2c);
    expect($community->secret_type)->toBe(SecretType::SnmpCommunity);
    expect($community->data)->toBe(['community' => 'split-test']);
    expect(Secret::find($v1))->toBeNull();

    $v3Secret = Secret::findOrFail($v3);
    expect($v3Secret->secret_type)->toBe(SecretType::SnmpV3);
    expect($v3Secret->data)->toEqual(['authlevel' => 'authNoPriv', 'authname' => 'user', 'authpass' => 'password1', 'authalgo' => 'SHA']);

    // net-snmp used root without a name, the passwords are kept
    expect(Secret::findOrFail($v3NoName)->data)->toEqual(['authlevel' => 'authPriv', 'authname' => 'root', 'authpass' => 'password2', 'cryptopass' => 'crypt2']);

    // net-snmp was given an empty community, the method keeps its secret
    $empty = Secret::findOrFail($versionOnly);
    expect($empty->secret_type)->toBe(SecretType::SnmpCommunity);
    expect($empty->data)->toBe(['community' => '']);

    expect($ipmi->fresh()->secret_type)->toBe(SecretType::Ipmi);

    // only v1 is a setting, the type of secret gives the rest
    assertMethod($v2cMethod, $v2c, []);
    assertMethod($v1Method, $v2c, ['port' => 1161, 'version' => 'v1']);
    assertMethod($v3Method, $v3, []);
    assertMethod($v3NoNameMethod, $v3NoName, []);
    assertMethod($versionOnlyMethod, $versionOnly, ['version' => 'v1']);

    // the order is kept, with the merged secret replaced
    expect(json_decode(DB::table('config')->where('config_name', 'snmp.default_credentials')->value('config_value'), true))->toBe([$v2c, $otherDefault->id, $versionOnly, $v3]);

    // nothing left to do
    migration()->up();
    expect($community->fresh()->secret_type)->toBe(SecretType::SnmpCommunity);
});

test('arun after afailed run still merges and replaces defaults', function () {
    // split by the run that failed
    $split = Secret::create(['description' => 'rerun split', 'secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'rerun']]);
    $v1 = oldSecret('rerun v1', ['version' => 'v1', 'community' => 'rerun']);
    $v1Method = snmpMethod($v1);
    DB::table('config')->updateOrInsert(['config_name' => 'snmp.default_credentials'], ['config_value' => json_encode([$v1, $split->id])]);

    migration()->up();

    expect(Secret::find($v1))->toBeNull();
    assertMethod($v1Method, $split->id, ['version' => 'v1']);
    expect(json_decode(DB::table('config')->where('config_name', 'snmp.default_credentials')->value('config_value'), true))->toBe([$split->id]);
});

test('down gives each version of acommunity its own secret', function () {
    $v2c = oldSecret('down v2c', ['version' => 'v2c', 'community' => 'split-test']);
    $v1 = oldSecret('down v1', ['version' => 'v1', 'community' => 'split-test']);
    $v3 = oldSecret('down v3', ['version' => 'v3', 'authlevel' => 'noAuthNoPriv', 'authname' => 'user']);
    $v2cMethod = snmpMethod($v2c);
    $v1Method = snmpMethod($v1, ['port' => 1161]);
    $v3Method = snmpMethod($v3);

    migration()->up();
    migration()->down();

    expect(rawSecret(DevicePollingMethod::findOrFail($v2cMethod)->secret_id))->toBe(['snmp', ['version' => 'v2c', 'community' => 'split-test']]);
    expect(rawSecret(DevicePollingMethod::findOrFail($v3Method)->secret_id))->toBe(['snmp', ['version' => 'v3', 'authlevel' => 'noAuthNoPriv', 'authname' => 'user']]);

    $v1Snmp = DevicePollingMethod::findOrFail($v1Method);
    $this->assertNotSame($v2c, $v1Snmp->secret_id);
    expect(rawSecret($v1Snmp->secret_id))->toBe(['snmp', ['version' => 'v1', 'community' => 'split-test']]);
    expect(DB::table('secrets')->where('id', $v1Snmp->secret_id)->value('description'))->toBe('down v2c (v1)');
    expect($v1Snmp->settings)->toBe(['port' => 1161]);
});

/**
 * @return array{string, array<string, mixed>}
 */
function rawSecret(?int $id): array
{
    $secret = DB::table('secrets')->where('id', $id)->first();
    expect($secret)->not->toBeNull();

    return [$secret->secret_type, json_decode((string) decrypt($secret->data), true)];
}

/**
 * @param  array<string, mixed>  $data
 */
function oldSecret(string $description, array $data): int
{
    return DB::table('secrets')->insertGetId([
        'description' => $description,
        'secret_type' => 'snmp',
        'data' => encrypt(json_encode($data)),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * @param  array<string, mixed>  $settings
 */
function snmpMethod(?int $secretId, array $settings = []): int
{
    return DB::table('device_polling_methods')->insertGetId([
        'device_id' => Device::factory()->create()->device_id,
        'method_type' => PollingMethodType::Snmp->value,
        'secret_id' => $secretId,
        'settings' => json_encode($settings),
    ]);
}

/**
 * @param  array<string, mixed>  $settings
 */
function assertMethod(int $id, ?int $secretId, array $settings): void
{
    $method = DevicePollingMethod::findOrFail($id);
    expect($method->secret_id)->toBe($secretId);
    expect($method->settings)->toEqual($settings);
}

function migration(): object
{
    return require base_path('database/migrations/2026_10_20_000000_split_snmp_secrets.php');
}