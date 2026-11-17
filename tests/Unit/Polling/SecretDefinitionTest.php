<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Enum\SecretType;
use LibreNMS\Polling\Secrets\Data\IpmiSecretData;
use LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData;
use LibreNMS\Polling\Secrets\Data\SnmpV3SecretData;
use LibreNMS\Polling\Secrets\Definitions\SecretDefinition;
use LibreNMS\Polling\Secrets\Definitions\SnmpCommunitySecretDefinition;
use LibreNMS\Polling\Secrets\Definitions\SnmpV3SecretDefinition;

test('mask hides only sensitive values', function () {
    $masked = (new SnmpV3SecretDefinition)->mask(['authname' => 'user', 'authpass' => 'secret', 'cryptopass' => '']);

    expect($masked)->toBe(['authname' => 'user', 'authpass' => SecretDefinition::MASK, 'cryptopass' => '']);
    expect((new SnmpCommunitySecretDefinition)->mask(['community' => 'public']))->toBe(['community' => SecretDefinition::MASK]);
});

test('unmask restores unchanged values', function () {
    $original = ['authname' => 'user', 'authpass' => 'secret', 'cryptopass' => 'crypt'];
    $submitted = ['authname' => 'user', 'authpass' => SecretDefinition::MASK, 'cryptopass' => 'changed'];

    expect((new SnmpV3SecretDefinition)->unmask($submitted, $original))->toBe(['authname' => 'user', 'authpass' => 'secret', 'cryptopass' => 'changed']);
});

test('schema defaults', function () {
    expect(SecretType::SnmpCommunity->definition()->schemaDefaults())->toBe([]);
    expect(SecretType::SnmpV3->definition()->schemaDefaults())->toBe(['authlevel' => 'noAuthNoPriv', 'authalgo' => 'MD5', 'cryptoalgo' => 'AES']);
});

test('snmp v3 secret data defaults match the form', function () {
    expect(SnmpV3SecretData::fromArray([])->toArray())->toEqual(SecretType::SnmpV3->definition()->schemaDefaults());
});

test('no snmp field has aversion', function () {
    foreach ([SecretType::SnmpCommunity, SecretType::SnmpV3] as $type) {
        $this->assertArrayNotHasKey('version', $type->definition()->fields(), "$type->value secrets must not hold the version, it is a polling method setting");
    }
});

test('data fills defaults', function () {
    $community = SecretType::SnmpCommunity->data(['community' => 'public']);
    expect($community)->toBeInstanceOf(SnmpCommunitySecretData::class);
    expect($community->community)->toBe('public');

    $v3 = SecretType::SnmpV3->data(['authname' => 'admin']);
    expect($v3)->toBeInstanceOf(SnmpV3SecretData::class);
    expect($v3->authname)->toBe('admin');
    expect($v3->authlevel)->toBe('noAuthNoPriv');

    $ipmi = IpmiSecretData::fromArray(['username' => 'admin']);
    expect($ipmi->username)->toBe('admin');
    expect($ipmi->password)->toBe('');
});
