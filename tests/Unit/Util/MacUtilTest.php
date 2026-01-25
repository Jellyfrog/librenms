<?php

use LibreNMS\Util\Mac;

test('mac output', function () {
    $mac = Mac::parse('DeadBeefa0c3');
    expect($mac->isValid())->toBeTrue();
    expect($mac->readable())->toBe('de:ad:be:ef:a0:c3');
    expect($mac->hex())->toBe('deadbeefa0c3');
    expect($mac->oid())->toBe('222.173.190.239.160.195');
    expect($mac->array())->toBe(['de', 'ad', 'be', 'ef', 'a0', 'c3']);
});

test('partial', function () {
    expect(Mac::parsePartial('0000.0c')->hex())->toBe('00000c000000');
    expect(Mac::parsePartial('08:00:20')->hex())->toBe('080020000000');
    expect(Mac::parsePartial('8::20')->hex())->toBe('080020000000');
    expect(Mac::parsePartial('aaaaaa')->hex())->toBe('aaaaaa000000');
    expect(Mac::parsePartial('aaaaa')->hex())->toBe('aaaaa0000000');
    expect(Mac::parsePartial('aaaaaa12')->hex())->toBe('aaaaaa120000');
    expect(Mac::parsePartial('aaaaaa123')->hex())->toBe('aaaaaa123000');
});

test('bridge parsing', function () {
    expect(Mac::parseBridge('80 62 0c 85 25 5c e5 00')->hex())->toBe('0c85255ce500');
    expect(Mac::parseBridge('00 00 00 00 00 00 00 01 ')->hex())->toBe('000000000001');
    expect(Mac::parseBridge('0-00.00.00.00.00.02')->hex())->toBe('000000000002');
    expect(Mac::parseBridge('0:18:6e:64:49:a0')->hex())->toBe('00186e6449a0');
    expect(Mac::parseBridge('80620c85255ce500')->hex())->toBe('0c85255ce500');
});

test('mac to hex', function (string $from, string $to) {
    expect(Mac::parse($from)->hex())->toBe($to);
})->with([
    ['00:00:00:00:00:01', '000000000001'],
    ['00-00-00-00-00-01', '000000000001'],
    ['000000.000001', '000000000001'],
    ['000000000001', '000000000001'],
    ['00:12:34:ab:cd:ef', '001234abcdef'],
    ['00:12:34:AB:CD:EF', '001234abcdef'],
    ['0:12:34:AB:CD:EF', '001234abcdef'],
    ['00-12-34-AB-CD-EF', '001234abcdef'],
    ['001234-ABCDEF', '001234abcdef'],
    ['0012.34AB.CDEF', '001234abcdef'],
    ['00:02:04:0B:0D:0F', '0002040b0d0f'],
    ['0:2:4:B:D:F', '0002040b0d0f'],
    ['0:2:4:B:D:F', '0002040b0d0f'],
    ['00d9.d110.21f9', '00d9d11021f9'],
    ['garbage', ''],
]);
