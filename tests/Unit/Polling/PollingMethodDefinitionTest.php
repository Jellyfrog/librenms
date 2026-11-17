<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Polling\Method\Definitions\IpmiDefinition;
use LibreNMS\Polling\Method\Definitions\SnmpDefinition;
use LibreNMS\Polling\Method\Methods\IpmiPollingMethod;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Method\PollingMethodRegistry;

test('filter overrides keeps only set values', function () {
    $definition = new SnmpDefinition;

    // empty means default, set values are kept even when they match the default
    expect($definition->filterOverrides(['transport' => 'udp', 'port' => '1161', 'timeout' => '', 'retries' => null, 'unknown' => 'x']))->toBe(['transport' => 'udp', 'port' => 1161]);
    expect($definition->filterOverrides([]))->toBe([]);
});

test('filter overrides casts values', function () {
    expect((new SnmpDefinition)->filterOverrides(['timeout' => '2.5', 'retries' => '3', 'bulk' => '0', 'context' => 'vrf-a']))->toBe(['timeout' => 2.5, 'retries' => 3, 'bulk' => false, 'context' => 'vrf-a']);
    expect((new IpmiDefinition)->filterOverrides(['ciphersuite' => '3', 'type' => 'lanplus']))->toBe(['ciphersuite' => 3, 'type' => 'lanplus']);
});

test('filter overrides drops values that can not be used', function () {
    expect((new SnmpDefinition)->filterOverrides([
        'max_oid' => '0', // below min
        'port' => '70000', // above max
        'bulk' => 'maybe', // not a boolean
        'transport' => 'sctp', // not an option
        'port_association_mode' => '2', // legacy id, not an option
    ]))->toBe([]);
    expect((new SnmpDefinition)->filterOverrides(['port_association_mode' => 'ifname']))->toBe([]);
    // options are case sensitive
    expect((new IpmiDefinition)->filterOverrides(['type' => 'lanplus2']))->toBe([]);
});

test('settings fields show defaults', function () {
    $fields = collect((new SnmpDefinition)->settingsFields(['transport' => 'tcp', 'port' => 1161, 'max_repeaters' => 7] + (new SnmpPollingMethod)->defaults()))->keyBy('key');

    // selects get an empty "Default" option instead of preselecting a value
    expect($fields['transport']['default_option'])->toBe('Default (TCP)');
    $this->assertArrayNotHasKey('default', $fields['transport']);
    expect($fields['bulk']['default_option'])->toBe('Default (Yes)');

    // other fields show the default as a placeholder
    expect($fields['port']['placeholder'])->toBe('1161');
    expect($fields['max_repeaters']['placeholder'])->toBe('7');
    $this->assertArrayNotHasKey('placeholder', $fields['context']);

    // explicit placeholders are kept
    $ipmiFields = collect((new IpmiDefinition)->settingsFields((new IpmiPollingMethod)->defaults()))->keyBy('key');
    expect($ipmiFields['hostname']['placeholder'])->toBe("Default: device's hostname");
    expect($ipmiFields['timeout']['placeholder'])->toBe('Default: ipmitool');
    // ipmitool's default differs by interface
    expect($ipmiFields['type']['default_option'])->toBe('Auto-detect');
});

test('bulk field shows no for v1', function () {
    $fields = collect((new SnmpDefinition)->settingsFields((new SnmpPollingMethod)->defaults()))->keyBy('key');
    expect($fields['bulk']['fixed_if_expression'])->toBe('settingsData["version"] === "v1"');
    expect($fields['bulk']['fixed_text'])->toBe('No');
    expect($fields['bulk']['visible_if_expression'])->toBeNull();

    // stays in place
    // the version is only used with a community, a stale v1 does not disable bulk for a v3 secret
    $fields = collect((new SnmpDefinition)->settingsFields((new SnmpPollingMethod)->defaults(), secretTypeVar: 'secretType'))->keyBy('key');
    expect($fields['bulk']['fixed_if_expression'])->toBe('settingsData["version"] === "v1" && (!secretType || ["snmp-community"].includes(secretType))');
});

test('every setting is optional', function () {
    foreach (PollingMethodType::cases() as $type) {
        foreach ((new PollingMethodRegistry)->get($type)->definition()->rules() as $field => $rules) {
            expect((array) $rules)->toContain('nullable', "{$type->value} setting $field should be nullable");
        }
    }
});
