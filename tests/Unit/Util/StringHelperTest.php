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

test('infer encoding', function (): void {
    expect(StringHelpers::inferEncoding(null))->toEqual(null);
    expect(StringHelpers::inferEncoding(''))->toEqual('');
    expect(StringHelpers::inferEncoding('~null'))->toEqual('~null');
    expect(StringHelpers::inferEncoding('Øverbyvegen'))->toEqual('Øverbyvegen');

    expect(StringHelpers::inferEncoding(base64_decode('w5h2ZXJieXZlZ2Vu')))->toEqual('Øverbyvegen');
    expect(StringHelpers::inferEncoding(base64_decode('2HZlcmJ5dmVnZW4=')))->toEqual('Øverbyvegen');
    expect(StringHelpers::inferEncoding(base64_decode('vcy/xs34SVB2NA==')))->toEqual('教科网IPv4');

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
