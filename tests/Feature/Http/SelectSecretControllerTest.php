<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Secret;
use App\Models\User;
use LibreNMS\Enum\SecretType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Permission::findOrCreate('secret.view');
});

test('select secrets returns paginated results', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $snmpSecret = Secret::create([
        'description' => 'SNMP Secret 1',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $ipmiSecret = Secret::create([
        'description' => 'IPMI Secret 1',
        'secret_type' => SecretType::Ipmi,
        'data' => ['user' => 'admin', 'password' => 'secret'],
    ]);

    $response = $this->actingAs($admin)->getJson(route('ajax.select.secret'));

    $response->assertOk();
    $response->assertJsonStructure(['results' => [['id', 'text']], 'pagination' => ['more']]);

    $results = $response->collect('results');
    expect($results->contains('id', $snmpSecret->id))->toBeTrue();
    expect($results->contains('id', $ipmiSecret->id))->toBeTrue();
});

test('select secrets filters by secret type', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $snmpSecret = Secret::create([
        'description' => 'SNMP Secret 1',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $ipmiSecret = Secret::create([
        'description' => 'IPMI Secret 1',
        'secret_type' => SecretType::Ipmi,
        'data' => ['user' => 'admin', 'password' => 'secret'],
    ]);

    $v3Secret = Secret::create([
        'description' => 'SNMP v3 Secret 1',
        'secret_type' => SecretType::SnmpV3,
        'data' => ['authlevel' => 'noAuthNoPriv', 'authname' => 'user'],
    ]);

    $response = $this->actingAs($admin)->getJson(route('ajax.select.secret', ['secret_type' => 'snmp-community']));

    $response->assertOk();
    $results = $response->collect('results');
    expect($results->contains('id', $snmpSecret->id))->toBeTrue();
    expect($results->contains('id', $v3Secret->id))->toBeFalse();
    expect($results->contains('id', $ipmiSecret->id))->toBeFalse();

    // several types, like the types a polling method can use
    $results = $this->actingAs($admin)->getJson(route('ajax.select.secret', ['secret_type' => 'snmp-community,snmp-v3']))
        ->assertOk()
        ->collect('results');
    expect($results->contains('id', $snmpSecret->id))->toBeTrue();
    expect($results->contains('id', $v3Secret->id))->toBeTrue();
    expect($results->contains('id', $ipmiSecret->id))->toBeFalse();
});

test('select secrets filters by type param', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $snmpSecret = Secret::create([
        'description' => 'SNMP Secret 1',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $ipmiSecret = Secret::create([
        'description' => 'IPMI Secret 1',
        'secret_type' => SecretType::Ipmi,
        'data' => ['user' => 'admin', 'password' => 'secret'],
    ]);

    $response = $this->actingAs($admin)->getJson(route('ajax.select.secret', ['type' => 'ipmi']));

    $response->assertOk();
    $results = $response->collect('results');
    expect($results->contains('id', $ipmiSecret->id))->toBeTrue();
    expect($results->contains('id', $snmpSecret->id))->toBeFalse();
});

test('select secrets searches by description', function () {
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $prodSecret = Secret::create([
        'description' => 'Unique Production Router Secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $stageSecret = Secret::create([
        'description' => 'Unique Staging Switch Secret',
        'secret_type' => SecretType::SnmpCommunity,
        'data' => ['community' => 'public'],
    ]);

    $response = $this->actingAs($admin)->getJson(route('ajax.select.secret', ['term' => 'Unique Production Router']));

    $response->assertOk();
    $results = $response->collect('results');
    expect($results->contains('id', $prodSecret->id))->toBeTrue();
    expect($results->contains('id', $stageSecret->id))->toBeFalse();
});