<?php

use LibreNMS\Util\Mac;

uses(LibreNMS\Tests\TestCase::class);

test('mac output', function (): void {
    $mac = Mac::parse('DeadBeefa0c3');
    expect($mac->isValid())->toBeTrue();
    expect($mac->readable())->toEqual('de:ad:be:ef:a0:c3');
    expect($mac->hex())->toEqual('deadbeefa0c3');
    expect($mac->oid())->toEqual('222.173.190.239.160.195');
    expect($mac->array())->toEqual(['de', 'ad', 'be', 'ef', 'a0', 'c3']);
});

test('partial', function (): void {
    expect(Mac::parsePartial('0000.0c')->hex())->toEqual('00000c000000');
    expect(Mac::parsePartial('08:00:20')->hex())->toEqual('080020000000');
    expect(Mac::parsePartial('8::20')->hex())->toEqual('080020000000');
    expect(Mac::parsePartial('aaaaaa')->hex())->toEqual('aaaaaa000000');
    expect(Mac::parsePartial('aaaaa')->hex())->toEqual('aaaaa0000000');
    expect(Mac::parsePartial('aaaaaa12')->hex())->toEqual('aaaaaa120000');
    expect(Mac::parsePartial('aaaaaa123')->hex())->toEqual('aaaaaa123000');
});

test('bridge parsing', function (): void {
    expect(Mac::parseBridge('80 62 0c 85 25 5c e5 00')->hex())->toEqual('0c85255ce500');
    expect(Mac::parseBridge('00 00 00 00 00 00 00 01 ')->hex())->toEqual('000000000001');
    expect(Mac::parseBridge('0-00.00.00.00.00.02')->hex())->toEqual('000000000002');
    expect(Mac::parseBridge('0:18:6e:64:49:a0')->hex())->toEqual('00186e6449a0');
    expect(Mac::parseBridge('80620c85255ce500')->hex())->toEqual('0c85255ce500');
});

test('mac to hex', function (string $from, string $to): void {
    expect(Mac::parse($from)->hex())->toEqual($to);
})->with('validMacProvider');

dataset('validMacProvider', fn () => [
    ['00:00:00:00:00:01', '000000000001'],
    ['00-00-00-00-00-01', '000000000001'],
    ['000000.000001',     '000000000001'],
    ['000000000001',      '000000000001'],
    ['00:12:34:ab:cd:ef', '001234abcdef'],
    ['00:12:34:AB:CD:EF', '001234abcdef'],
    ['0:12:34:AB:CD:EF',  '001234abcdef'],
    ['00-12-34-AB-CD-EF', '001234abcdef'],
    ['001234-ABCDEF',     '001234abcdef'],
    ['0012.34AB.CDEF',    '001234abcdef'],
    ['00:02:04:0B:0D:0F', '0002040b0d0f'],
    ['0:2:4:B:D:F',       '0002040b0d0f'],
    ['0:2:4:B:D:F',       '0002040b0d0f'],
    ['00d9.d110.21f9',    '00d9d11021f9'],
    ['garbage',           ''],
]);
