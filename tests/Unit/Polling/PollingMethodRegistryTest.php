<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;
use LibreNMS\Polling\Method\Methods\IcmpPollingMethod;
use LibreNMS\Polling\Method\Methods\IpmiPollingMethod;
use LibreNMS\Polling\Method\Methods\ProxmoxApiPollingMethod;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Method\Methods\UnixAgentPollingMethod;
use LibreNMS\Polling\Method\PollingMethodRegistry;

test('every type has a method', function (): void {
    $registry = new PollingMethodRegistry;

    expect($registry->get(PollingMethodType::Icmp))->toBeInstanceOf(IcmpPollingMethod::class);
    expect($registry->get(PollingMethodType::Ipmi))->toBeInstanceOf(IpmiPollingMethod::class);
    expect($registry->get(PollingMethodType::ProxmoxApi))->toBeInstanceOf(ProxmoxApiPollingMethod::class);
    expect($registry->get(PollingMethodType::Snmp))->toBeInstanceOf(SnmpPollingMethod::class);
    expect($registry->get(PollingMethodType::UnixAgent))->toBeInstanceOf(UnixAgentPollingMethod::class);
});

test('every setting has a default and label', function (): void {
    foreach (PollingMethodType::cases() as $type) {
        $method = (new PollingMethodRegistry)->get($type);
        $fields = array_keys($method->definition()->fields());

        expect(array_keys($method->defaults()))->toEqualCanonicalizing($fields, "{$type->value} default settings do not match its fields");

        foreach ($fields as $key) {
            expect(trans()->has("poller.method_settings.{$type->value}.$key"))->toBeTrue("{$type->value} setting $key has no label");
        }
    }
});

test('method secret types', function (): void {
    $registry = new PollingMethodRegistry;

    expect($registry->get(PollingMethodType::Icmp)->secretTypes())->toBe([]);
    expect($registry->get(PollingMethodType::Ipmi)->secretTypes())->toBe([SecretType::Ipmi]);
    expect($registry->get(PollingMethodType::ProxmoxApi)->secretTypes())->toBe([SecretType::ProxmoxApi]);
    expect($registry->get(PollingMethodType::UnixAgent)->secretTypes())->toBe([]);

    $snmp = $registry->get(PollingMethodType::Snmp);
    expect($snmp->secretTypes())->toBe([SecretType::SnmpCommunity, SecretType::SnmpV3]);
});

test('secret type from', function (): void {
    $registry = new PollingMethodRegistry;
    $snmp = $registry->get(PollingMethodType::Snmp);
    $ipmi = $registry->get(PollingMethodType::Ipmi);

    expect($snmp->secretTypeFrom('snmp-v3'))->toBe(SecretType::SnmpV3);
    expect($snmp->secretTypeFrom(SecretType::SnmpCommunity))->toBe(SecretType::SnmpCommunity);
    expect($snmp->secretTypeFrom(null))->toBeNull('a type must be chosen from several');
    expect($snmp->secretTypeFrom('ipmi'))->toBeNull('snmp does not use ipmi secrets');
    expect($snmp->secretTypeFrom('snmp'))->toBeNull('the old type is gone');

    expect($ipmi->secretTypeFrom(null))->toBe(SecretType::Ipmi, 'the only type is chosen');
    expect($ipmi->secretTypeFrom('snmp-v3'))->toBeNull();
    expect($registry->get(PollingMethodType::Icmp)->secretTypeFrom(null))->toBeNull();
});

test('config rejects a secret of another type', function (): void {
    $deviceMethod = new DevicePollingMethod(['method_type' => PollingMethodType::Snmp]);
    $deviceMethod->setRelation('secret', new Secret(['secret_type' => SecretType::Ipmi, 'data' => ['username' => 'admin']]));

    $this->expectException(UnexpectedValueException::class);
    (new SnmpPollingMethod)->config(new Device, $deviceMethod);
});

test('only snmp and icmp affect availability by default', function (): void {
    $registry = new PollingMethodRegistry;

    expect($registry->get(PollingMethodType::Icmp)->defaultAffectsAvailability())->toBeTrue();
    expect($registry->get(PollingMethodType::Ipmi)->defaultAffectsAvailability())->toBeFalse();
    expect($registry->get(PollingMethodType::ProxmoxApi)->defaultAffectsAvailability())->toBeFalse();
    expect($registry->get(PollingMethodType::Snmp)->defaultAffectsAvailability())->toBeTrue();
    expect($registry->get(PollingMethodType::UnixAgent)->defaultAffectsAvailability())->toBeFalse();
});
