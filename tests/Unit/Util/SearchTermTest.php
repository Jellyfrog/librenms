<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Util\IPv4;
use LibreNMS\Util\IPv6;
use LibreNMS\Util\SearchTerm;
use PHPUnit\Framework\Attributes\DataProvider;


test('exact ipv4', function () {
    $term = SearchTerm::parse(' 192.168.1.10 ');

    expect($term->value)->toBe('192.168.1.10');
    expect($term->ip)->toBeInstanceOf(IPv4::class);
    expect($term->isExactIp())->toBeTrue();
    expect($term->isIpv4())->toBeTrue();
    expect($term->isIpv6())->toBeFalse();
    expect($term->isMac())->toBeFalse();
    // 192168110 is hex, but an IP
    expect($term->networkRange())->toBeNull();
});

test('ipv4 network', function () {
    $term = SearchTerm::parse('10.1.2.3/23');

    expect($term->isNetwork)->toBeTrue();
    expect($term->isExactIp())->toBeFalse();
    expect($term->networkRange())->toBe(['0A010200', '0A0103FF']);
});

test('ipv6', function () {
    $term = SearchTerm::parse('2001:db8::1');
    expect($term->ip)->toBeInstanceOf(IPv6::class);
    expect($term->isExactIp())->toBeTrue();
    expect($term->isIpv6())->toBeTrue();
    expect($term->isMac())->toBeFalse();

    $network = SearchTerm::parse('2001:db8::/32');
    expect($network->networkRange())->toBe(['20010DB8000000000000000000000000', '20010DB8FFFFFFFFFFFFFFFFFFFFFFFF']);
});

test('fragments', function (string $search, bool $ipv4, bool $ipv6, ?string $macHex) {
    $term = SearchTerm::parse($search);

    expect($term->ip)->toBeNull();
    expect($term->isIpv4())->toBe($ipv4, 'ipv4');
    expect($term->isIpv6())->toBe($ipv6, 'ipv6');
    expect($term->macHex)->toBe($macHex, 'mac');
})->with('fragments');

/**
 * @return array<string, array{string, bool, bool, ?string}>
 */
dataset('fragments', function () {
    return [
        'ipv4 prefix' => ['10.1.', true, false, null],
        'ipv4 suffix' => ['.10', true, false, null],
        'ipv6 start' => ['2001:db8:', false, true, '2001db8'],
        'partial mac colons' => ['74:29:72', false, true, '742972'],
        'partial mac cisco' => ['0011.2233', false, false, '00112233'],
        'short hex word' => ['cafe', false, false, null],
        'six hex digits' => ['abcdef', false, false, 'abcdef'],
        'text' => ['core-sw1', false, false, null],
    ];
});

test('full mac', function (string $search) {
    $term = SearchTerm::parse($search);

    expect($term->isFullMac())->toBeTrue();
    expect($term->macHex)->toBe('001122aabbcc');
    expect($term->isIp())->toBeFalse();
})->with('fullMacs');

/**
 * @return list<array{string}>
 */
dataset('fullMacs', function () {
    return [
        ['00:11:22:AA:BB:CC'],
        ['00-11-22-aa-bb-cc'],
        ['0011.22aa.bbcc'],
        ['001122aabbcc'],
        ['0:11:22:aa:bb:cc'],
    ];
});

test('hostname', function () {
    expect(SearchTerm::parse('printer01.example.com')->isHostname())->toBeTrue();
    expect(SearchTerm::parse('printer01.example.com.')->isHostname())->toBeTrue();
    expect(SearchTerm::parse('printer01')->isHostname())->toBeFalse();
    expect(SearchTerm::parse('10.0.0.1')->isHostname())->toBeFalse();
    expect(SearchTerm::parse('not a host.name')->isHostname())->toBeFalse();
});

test('cleans pasted values', function (string $pasted, string $expected) {
    expect(SearchTerm::parse($pasted)->value)->toBe($expected);
})->with('pastedValues');

/**
 * @return array<string, array{string, string}>
 */
dataset('pastedValues', function () {
    return [
        'quoted' => ['"10.0.0.1"', '10.0.0.1'],
        'field name' => ['ip=10.0.0.1,', '10.0.0.1'],
        'mac field with space' => ['mac: 00:11:22:33:44:55', '00:11:22:33:44:55'],
        'bracketed ipv6 with port' => ['[2001:db8::1]:443', '2001:db8::1'],
        'ipv4 with port' => ['10.0.0.1:443', '10.0.0.1'],
        'parentheses' => ['(host.example.com)', 'host.example.com'],
        'plain text kept' => ['New York', 'New York'],
    ];
});

test('parse many splits pasted hosts', function () {
    $terms = SearchTerm::parseMany("10.0.0.1\n00:11:22:33:44:55, host.example.com;2001:db8::/32");

    expect(array_map(fn (SearchTerm $term) => $term->value, $terms))->toBe(['10.0.0.1', '00:11:22:33:44:55', 'host.example.com', '2001:db8::/32']);
});

test('parse many keeps text together', function () {
    expect(array_map(fn (SearchTerm $term) => $term->value, SearchTerm::parseMany('core switch')))->toBe(['core switch']);
    expect(array_map(fn (SearchTerm $term) => $term->value, SearchTerm::parseMany('10.0.0.1 core')))->toBe(['10.0.0.1 core']);
});

test('parse many of nothing has no terms', function () {
    expect(SearchTerm::parseMany(''))->toBe([]);
    expect(SearchTerm::parseMany(' , ; '))->toBe([]);
});

test('host and port keeps the address', function () {
    $term = SearchTerm::parse('10.0.0.1:443');

    expect($term->value)->toBe('10.0.0.1');
    expect($term->isExactIp())->toBeTrue();
});

test('like escapes wildcards', function () {
    expect(SearchTerm::parse('core')->like())->toBe('%core%');
    expect(SearchTerm::parse('a_b%c')->like())->toBe('%a\\_b\\%c%');
});

test('bare hex may be text', function () {
    expect(SearchTerm::parse('123456789012')->isBareHex())->toBeTrue();
    expect(SearchTerm::parse('123456789012')->mayBeText())->toBeTrue();
    expect(SearchTerm::parse('12:34:56:78:90:12')->mayBeText())->toBeFalse();
    expect(SearchTerm::parse('10.0.0.1')->mayBeText())->toBeFalse();
});

test('may be partial ipv6', function () {
    expect(SearchTerm::parse('fe80::1:2')->mayBePartialIpv6())->toBeTrue();
    expect(SearchTerm::parse('2001:db8:1:2:3:4:5:6')->mayBePartialIpv6())->toBeFalse();
    expect(SearchTerm::parse('10.0.0.1')->mayBePartialIpv6())->toBeFalse();
});
