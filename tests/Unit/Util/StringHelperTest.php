<?php

/*
 * StringHelperTest.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2021 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use LibreNMS\Util\StringHelpers;

uses(LibreNMS\Tests\TestCase::class);

test('valid utf8', function (): void {
    expect(StringHelpers::isValidUtf8('Øverbyvegen'))->toBeTrue();
    expect(StringHelpers::isValidUtf8("\xD8verbyvegen"))->toBeFalse();
});

test('infer encoding', function (): void {
    expect(StringHelpers::inferEncoding(null))->toEqual(null);
    expect(StringHelpers::inferEncoding(''))->toEqual('');
    expect(StringHelpers::inferEncoding('~null'))->toEqual('~null');
    expect(StringHelpers::inferEncoding('Øverbyvegen'))->toEqual('Øverbyvegen');
    expect(StringHelpers::inferEncoding("first\xDAsecond"))->toEqual("first\nsecond");

    expect(StringHelpers::inferEncoding(base64_decode('w5h2ZXJieXZlZ2Vu')))->toEqual('Øverbyvegen');
    expect(StringHelpers::inferEncoding(base64_decode('2HZlcmJ5dmVnZW4=')))->toEqual('Øverbyvegen');
    expect(StringHelpers::inferEncoding(base64_decode('vcy/xs34SVB2NA==')))->toEqual('教科网IPv4');

    // Huawei SmartAX MA5608T returns GBK-encoded "风机盒" (fan box) in
    // ENTITY-MIB::entPhysicalDescr, which crashed discovery. See #20361
    expect(StringHelpers::inferEncoding("MA5610&MA5616\xB7\xE7\xBB\xFA\xBA\xD0"))->toEqual('MA5610&MA5616风机盒');

    config(['app.charset' => 'Shift_JIS']);
    expect(StringHelpers::inferEncoding(base64_decode('g1KDk4NUgVuDZw==')))->toEqual('コンサート');
});

test('is stringable', function (): void {
    expect(StringHelpers::isStringable(null))->toBeTrue();
    expect(StringHelpers::isStringable(''))->toBeTrue();
    expect(StringHelpers::isStringable('string'))->toBeTrue();
    expect(StringHelpers::isStringable(-1))->toBeTrue();
    expect(StringHelpers::isStringable(1.0))->toBeTrue();
    expect(StringHelpers::isStringable(false))->toBeTrue();

    expect(StringHelpers::isStringable([]))->toBeFalse();
    expect(StringHelpers::isStringable((object) []))->toBeFalse();

    $stringable = new class
    {
        public function __toString()
        {
            return '';
        }
    };
    expect(StringHelpers::isStringable($stringable))->toBeTrue();

    $nonstringable = new class {
    };
    expect(StringHelpers::isStringable($nonstringable))->toBeFalse();
});

test('is hex string', function (): void {
    expect(StringHelpers::isHex('af'))->toBeTrue();
    expect(StringHelpers::isHex('28'))->toBeTrue();
    expect(StringHelpers::isHex('aF28'))->toBeTrue();
    expect(StringHelpers::isHex('a'))->toBeFalse();
    expect(StringHelpers::isHex('aF 28'))->toBeFalse();
    expect(StringHelpers::isHex('aF 2'))->toBeFalse();
    expect(StringHelpers::isHex('aG'))->toBeFalse();
});

test('is hex with delimiters', function (): void {
    expect(StringHelpers::isHex('af 28 02', ' '))->toBeTrue();
    expect(StringHelpers::isHex('aF 28 02 CE', ' '))->toBeTrue();
    expect(StringHelpers::isHex('a5 fj 53', ' '))->toBeFalse();
    expect(StringHelpers::isHex('a5fe53', ' '))->toBeFalse();

    expect(StringHelpers::isHex('af 28 02', ':'))->toBeFalse();
    expect(StringHelpers::isHex('af:28:02', ':'))->toBeTrue();
    expect(StringHelpers::isHex('aF:28:02:CE', ':'))->toBeTrue();
    expect(StringHelpers::isHex('a5:fj:53', ':'))->toBeFalse();
    expect(StringHelpers::isHex('a5fe53', ':'))->toBeFalse();
});

test('decode snmp hex text', function (): void {
    // off by one string length (trailing null)
    expect(StringHelpers::decodeSnmpHexText('32 34 35 30 30 37 39 33 00'))->toBe('24500793');
    // net-snmp wraps long hex output
    expect(StringHelpers::decodeSnmpHexText("44 49 4E 49 4F 4E 20 49 50 20 73 74 61 72 6C 69\n67 68 74 20 37 30 30 30 20 48 44 00"))->toBe('DINION IP starlight 7000 HD');
    // non-ASCII text
    expect(StringHelpers::decodeSnmpHexText('E9 BB 91 E8 89 B2 E7 A2 B3 E7 B2 89'))->toBe('黑色碳粉');
    expect(StringHelpers::decodeSnmpHexText('E9 BB 91 E8 89 B2 00'))->toBe('黑色');

    // unchanged: not hex, or printable ASCII that net-snmp would not have printed as hex
    expect(StringHelpers::decodeSnmpHexText('plain text'))->toBe('plain text');
    expect(StringHelpers::decodeSnmpHexText('41 42'))->toBe('41 42');
    expect(StringHelpers::decodeSnmpHexText(''))->toBe('');
});

test('has number', function (): void {
    expect(StringHelpers::hasNumber('123'))->toBeTrue();
    expect(StringHelpers::hasNumber('123.45'))->toBeTrue();
    expect(StringHelpers::hasNumber('123.'))->toBeTrue();
    expect(StringHelpers::hasNumber('.30'))->toBeTrue();
    expect(StringHelpers::hasNumber('.5'))->toBeTrue();
    expect(StringHelpers::hasNumber('-123'))->toBeTrue();
    expect(StringHelpers::hasNumber('-123.45'))->toBeTrue();
    expect(StringHelpers::hasNumber('-.30'))->toBeTrue();
    expect(StringHelpers::hasNumber('  123'))->toBeTrue();
    expect(StringHelpers::hasNumber('  .30'))->toBeTrue();
    expect(StringHelpers::hasNumber('0'))->toBeTrue();
    expect(StringHelpers::hasNumber('0.0'))->toBeTrue();
    expect(StringHelpers::hasNumber('123abc'))->toBeTrue();

    expect(StringHelpers::hasNumber(''))->toBeFalse();
    expect(StringHelpers::hasNumber('abc'))->toBeFalse();
    expect(StringHelpers::hasNumber('abc123'))->toBeFalse();
    expect(StringHelpers::hasNumber('.'))->toBeFalse();
    expect(StringHelpers::hasNumber('-.'))->toBeFalse();
    expect(StringHelpers::hasNumber('  abc'))->toBeFalse();
    expect(StringHelpers::hasNumber('  .'))->toBeFalse();
});
