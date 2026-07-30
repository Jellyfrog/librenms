<?php

use LibreNMS\Util\Oid;

uses(\LibreNMS\Tests\TestCase::class);

test('string from oid single', function () {
    // 3 characters: 'A' (65) 'B' (66) 'C' (67)
    $oid = '3.65.66.67';
    expect(Oid::stringFromOid($oid))->toBe('ABC');
    // default 's' extracts first string
    expect(Oid::stringFromOid($oid, 's'))->toBe('ABC');
    // explicit
});

test('string from oid multiple positions', function () {
    // two strings: 'ABC' and 'xy'
    $oid = '3.65.66.67.2.120.121';
    expect(Oid::stringFromOid($oid, 's'))->toBe('ABC');
    expect(Oid::stringFromOid($oid, 'ss'))->toBe('xy');
    // skip first string, extract second
});

test('string from oid position out of bounds', function () {
    $oid = '1.90';
    // 'Z'
    expect(Oid::stringFromOid($oid, 's'))->toBe('Z');
    expect(Oid::stringFromOid($oid, 'ss'))->toBe('');
    // no second string present
});

test('string from oid zero length segment', function () {
    // three segments: 'ABC', '', 'Z'
    $oid = '3.65.66.67.0.1.90';
    expect(Oid::stringFromOid($oid, 's'))->toBe('ABC');
    expect(Oid::stringFromOid($oid, 'ss'))->toBe('');
    expect(Oid::stringFromOid($oid, 'sss'))->toBe('Z');
});

test('oid with combined numeric and string', function () {
    // first two indices are numeric (3, 49), followed by a string of length 7: 'Pre-Amp'
    $oid = '3.49.7.80.114.101.45.65.109.112';
    expect(Oid::stringFromOid($oid, 'nns'))->toBe('Pre-Amp');

    // sanity checks of other formats
    expect(Oid::stringFromOid($oid, 's'))->toBe("1\x07P");
    // first string length=3 -> 49,7,80 ("1\x07P")
    expect(Oid::stringFromOid($oid, 'ns'))->toBe('');
    // interpreting 49 as length also fails
});

test('string from oid high ascii bytes', function () {
    // bytes > 127 should be packed as-is
    // example: 0xC3 0xBC (UTF-8 bytes for 'ü') length 2
    $oid = '2.195.188';
    expect(Oid::stringFromOid($oid))->toBe("\xC3\xBC");
});

test('string from oid empty', function () {
    // zero length string segment
    $oid = '0';
    expect(Oid::stringFromOid($oid))->toBe('');
});

test('encode string', function () {
    $encoded = Oid::encodeString('ABC');
    expect($encoded->oid)->toBe('3.65.66.67');
});

test('encode string empty', function () {
    $encoded = Oid::encodeString('');
    expect($encoded->oid)->toBe('0');
});

test('is numeric', function () {
    expect(Oid::of('1.3.6.1')->isNumeric())->toBeTrue();
    expect(Oid::of('.1.3.6.1')->isNumeric())->toBeTrue();
    expect(Oid::of('IF-MIB::ifDescr.0')->isNumeric())->toBeFalse();
    expect(Oid::of('ifDescr.0')->isNumeric())->toBeFalse();
});

test('is full textual oid', function () {
    expect(Oid::of('IF-MIB::ifDescr')->isFullTextualOid())->toBeTrue();

    // still matches even with instance suffix
    expect(Oid::of('IF-MIB::ifDescr.0')->isFullTextualOid())->toBeTrue();
    expect(Oid::of('ifDescr.0')->isFullTextualOid())->toBeFalse();
    expect(Oid::of('1.3.6.1')->isFullTextualOid())->toBeFalse();
});

test('has mib and get mib', function () {
    expect(Oid::of('IF-MIB::ifDescr.0')->hasMib())->toBeTrue();
    expect(Oid::of('IF-MIB::ifDescr.0')->getMib())->toBe('IF-MIB');
    expect(Oid::of('ifDescr.0')->hasMib())->toBeFalse();
    expect(Oid::of('ifDescr.0')->getMib())->toBe('');
});

test('has numeric root', function () {
    expect(Oid::of('1.3.6.1')->hasNumericRoot())->toBeTrue();
    expect(Oid::of('.1.3.6.1')->hasNumericRoot())->toBeTrue();
    expect(Oid::of('2.3.6.1')->hasNumericRoot())->toBeFalse();
    expect(Oid::of('IF-MIB::ifDescr')->hasNumericRoot())->toBeFalse();
});

test('is valid', function () {
    expect(Oid::of('1.3.6.1')->isValid('1.3.6.1'))->toBeTrue();
    expect(Oid::of('IF-MIB::ifDescr')->isValid('IF-MIB::ifDescr'))->toBeTrue();
    expect(Oid::of('ifDescr')->isValid('ifDescr'))->toBeFalse();
});

test('has numeric static', function () {
    expect(Oid::hasNumeric(['IF-MIB::ifDescr', '1.3.6.1']))->toBeTrue();
    expect(Oid::hasNumeric(['IF-MIB::ifDescr', 'ifName.0']))->toBeFalse();
});

test('to string casts to original', function () {
    expect((string) Oid::of('1.2.3'))->toBe('1.2.3');
});
