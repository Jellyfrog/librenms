<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\DevicePollingMethod;
use App\Models\Secret;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;

test('known types are cast', function () {
    $secret = new Secret(['secret_type' => SecretType::SnmpV3]);
    expect($secret->secret_type)->toBe(SecretType::SnmpV3);
    expect($secret->typeName())->toBe('snmp-v3');
    expect($secret->typeLabel())->toBe(SecretType::SnmpV3->label());

    $method = new DevicePollingMethod(['method_type' => 'icmp']);
    expect($method->method_type)->toBe(PollingMethodType::Icmp);
    expect($method->toArray()['method_type'])->toBe('icmp');
});

test('unknown types keep the stored value', function () {
    $secret = new Secret(['secret_type' => 'future-secret']);
    expect($secret->secret_type)->toBe(SecretType::Unknown);
    expect($secret->typeName())->toBe('future-secret');
    expect($secret->typeLabel())->toBe('Unknown (future-secret)');

    // assigning the cast value back does not overwrite the stored type
    $secret->secret_type = $secret->secret_type;
    expect($secret->typeName())->toBe('future-secret');

    $method = new DevicePollingMethod(['method_type' => 'future-method']);
    $method->syncOriginal();
    expect($method->method_type)->toBe(PollingMethodType::Unknown);
    expect($method->toArray()['method_type'])->toBe('future-method');
    $method->method_type = PollingMethodType::Unknown;
    expect($method->isDirty('method_type'))->toBeFalse();
});

test('another enum is rejected', function () {
    $this->expectException(\InvalidArgumentException::class);
    new DevicePollingMethod(['method_type' => SecretType::Ipmi]);
});

test('unknown secret values are all masked', function () {
    $definition = SecretType::Unknown->definition();
    $data = ['pin' => '0', 'flag' => false, 'token' => 'abc', 'empty' => '', 'none' => null];
    $masked = $definition->mask($data);

    expect($masked)->toBe(['pin' => '********', 'flag' => '********', 'token' => '********', 'empty' => '', 'none' => null]);
    expect($definition->unmask($masked, $data))->toBe($data);
});

test('unknown is not valid input', function () {
    expect(SecretType::tryFromKnown('unknown'))->toBeNull();
    expect(PollingMethodType::tryFromKnown('unknown'))->toBeNull();
    expect(PollingMethodType::tryFromKnown('snmp'))->toBe(PollingMethodType::Snmp);
    expect(SecretType::known())->not->toContain(SecretType::Unknown);
    expect(PollingMethodType::known())->not->toContain(PollingMethodType::Unknown);
});
