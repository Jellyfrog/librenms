<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\OS\Timos;


test('null encap decodes', function () {
    expect(Timos::decodeEncapValue(0))->toBe(['outer' => 0, 'inner' => null]);
});

test('dot1q encap decodes', function () {
    expect(Timos::decodeEncapValue(500))->toBe(['outer' => 500, 'inner' => null]);
    expect(Timos::decodeEncapValue(1))->toBe(['outer' => 1, 'inner' => null]);
});

test('qin qencap decodes', function () {
    // outer 100, inner 200: inner VLAN lives in the upper 16 bits
    $encap = (200 << 16) | 100;
    expect(Timos::decodeEncapValue($encap))->toBe(['outer' => 100, 'inner' => 200]);
});

test('string input is accepted', function () {
    expect(Timos::decodeEncapValue('500'))->toBe(['outer' => 500, 'inner' => null]);
});

test('dot1q formats', function () {
    expect(Timos::formatEncapValue(500))->toBe('500');
    expect(Timos::formatEncapValue(0))->toBe('0');
});

test('qin qformats as outer dot inner', function () {
    expect(Timos::formatEncapValue((200 << 16) | 100))->toBe('100.200');
});

test('wildcard formats', function () {
    expect(Timos::formatEncapValue(4095))->toBe('*');
});
