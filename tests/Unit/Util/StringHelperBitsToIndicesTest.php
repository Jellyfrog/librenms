<?php

use LibreNMS\Util\StringHelpers;

include_once 'includes/functions.php';

test('basic functionality', function (): void {
    $result = StringHelpers::bitsToIndices('9a00');
    expect($result)->toEqual([1, 4, 5, 7]);
});

test('single hex digit', function (): void {
    // 'f' -> '0f' -> '00001111' -> [5, 6, 7, 8]
    $result = StringHelpers::bitsToIndices('f');
    expect($result)->toEqual([5, 6, 7, 8]);
});

test('odd number of digits', function (): void {
    // 'abc' -> '0abc' -> binary analysis
    $result = StringHelpers::bitsToIndices('abc');

    // '0abc' = 0000 1010 1011 1100
    // Byte 1: 0000 1010 -> positions 5, 7
    // Byte 2: 1011 1100 -> positions 9, 11, 12, 13, 14
    expect($result)->toEqual([5, 7, 9, 11, 12, 13, 14]);
});

test('all zeros', function (): void {
    $result = StringHelpers::bitsToIndices('0000');
    expect($result)->toEqual([]);
});

test('all ones', function (): void {
    $result = StringHelpers::bitsToIndices('ff');
    expect($result)->toEqual([1, 2, 3, 4, 5, 6, 7, 8]);
});

test('mixed case hex', function (): void {
    $result = StringHelpers::bitsToIndices('AbC');

    // Same as 'abc' test
    expect($result)->toBe([5, 7, 9, 11, 12, 13, 14]);
});

test('comma separated ports', function (): void {
    $result = StringHelpers::bitsToIndices('143,343,1024');
    expect($result)->toBe([143, 343, 1024]);

    $result = StringHelpers::bitsToIndices('143,343,a1024');
    expect($result)->toEqual([]);
});

test('with spaces', function (): void {
    $result = StringHelpers::bitsToIndices('9a 00');
    expect($result)->toEqual([1, 4, 5, 7]);
});

test('with newlines', function (): void {
    $result = StringHelpers::bitsToIndices("9a\n00");
    expect($result)->toEqual([1, 4, 5, 7]);
});

test('with mixed whitespace', function (): void {
    $result = StringHelpers::bitsToIndices("9a \n 00");
    expect($result)->toEqual([1, 4, 5, 7]);
});

test('empty string', function (): void {
    $result = StringHelpers::bitsToIndices('');
    expect($result)->toEqual([]);
});

test('invalid hex characters', function (): void {
    $result = StringHelpers::bitsToIndices('xyz');
    expect($result)->toEqual([]);
});

test('mixed valid invalid hex', function (): void {
    $result = StringHelpers::bitsToIndices('a1z3');
    expect($result)->toEqual([]);
});

test('longer hex string', function (): void {
    // 'ff00ff' -> 11111111 00000000 11111111
    // Positions: 1-8, 17-24
    $result = StringHelpers::bitsToIndices('ff00ff');
    $expected = array_merge(
        range(1, 8),    // First byte: ff
        range(17, 24)   // Third byte: ff
    );
    expect($result)->toEqual($expected);
});

test('bit position calculation', function (): void {
    // '80' -> 10000000 -> [1] (leftmost bit)
    $result = StringHelpers::bitsToIndices('80');
    expect($result)->toEqual([1]);

    // '01' -> 00000001 -> [8] (rightmost bit)
    $result = StringHelpers::bitsToIndices('01');
    expect($result)->toEqual([8]);

    // '8001' -> 10000000 00000001 -> [1, 16]
    $result = StringHelpers::bitsToIndices('8001');
    expect($result)->toEqual([1, 16]);
});

test('only whitespace', function (): void {
    $result = StringHelpers::bitsToIndices("  \n  ");
    expect($result)->toEqual([]);
});

test('single bit per nibble', function (): void {
    // '88' -> 10001000 -> [1, 5]
    $result = StringHelpers::bitsToIndices('88');
    expect($result)->toEqual([1, 5]);
});

test('alternating bits', function (): void {
    // 'aa' -> 10101010 -> [1, 3, 5, 7]
    $result = StringHelpers::bitsToIndices('aa');
    expect($result)->toEqual([1, 3, 5, 7]);

    // '55' -> 01010101 -> [2, 4, 6, 8]
    $result = StringHelpers::bitsToIndices('55');
    expect($result)->toEqual([2, 4, 6, 8]);
});

test('very long hex string', function (): void {
    // Test with 4 bytes: first and last byte set to 0x80
    $result = StringHelpers::bitsToIndices('80008080');

    // Byte 1: 10000000 -> [1]
    // Byte 2: 00000000 -> []
    // Byte 3: 10000000 -> [17]
    // Byte 4: 10000000 -> [25]
    expect($result)->toEqual([1, 17, 25]);
});

test('with data provider', function (string $hex, array $expected): void {
    $result = StringHelpers::bitsToIndices($hex);
    expect($result)->toEqual($expected);
})->with('hexDataProvider');

dataset('hexDataProvider', fn () => [
    'single_bit_first_position' => ['80', [1]],
    'single_bit_last_position' => ['01', [8]],
    'two_bytes_alternating' => ['aa55', [1, 3, 5, 7, 10, 12, 14, 16]],
    'three_bytes_pattern' => ['f0f0f0', [1, 2, 3, 4, 9, 10, 11, 12, 17, 18, 19, 20]],
    'zero_byte_in_middle' => ['ff00ff', array_merge(range(1, 8), range(17, 24))],
]);
