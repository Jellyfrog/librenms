<?php

/**
 * DbFacileTest.php
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 */

uses(\LibreNMS\Tests\TestCase::class);
use Illuminate\Support\Facades\DB;

test('place holders allows now', function () {
    $values = ['name' => 'test', 'updated' => ['NOW()']];

    expect(dbPlaceHolders($values))->toBe([':name', 'NOW()']);
    expect($values)->toBe(['name' => 'test']);
});

test('place holders does not splice arbitrary arrays', function () {
    $values = [
        'name' => ['(SELECT password FROM users LIMIT 1)'],
        'nested' => ['a' => ['b' => 'c']],
        'multi' => ['NOW()', 'extra'],
        'lower' => ['now()'],
    ];

    expect(dbPlaceHolders($values))->toBe([':name', ':nested', ':multi', ':lower']);
    expect($values)->toBe(['name' => null, 'nested' => null, 'multi' => null, 'lower' => null]);
});

test('raw sql expression', function () {
    expect(dbRawSqlExpression(['NOW()']))->toBe('NOW()');
    expect(dbRawSqlExpression(['UNIX_TIMESTAMP()']))->toBeNull();
    expect(dbRawSqlExpression(['1) OR (1=1']))->toBeNull();
    expect(dbRawSqlExpression([]))->toBeNull();
    expect(dbRawSqlExpression([['NOW()']]))->toBeNull();
});

test('update allows now', function () {
    $queries = DB::pretend(function (): void {
        dbUpdate(['value' => 'x', 'updated' => ['NOW()']], 'table', '`id` = ?', [5]);
    });

    expect($queries)->toHaveCount(1);
    expect($queries[0]['query'])->toBe("UPDATE `table` set `value`='x',`updated`=NOW() WHERE `id` = 5");
});

test('update does not splice arbitrary arrays', function () {
    $queries = DB::pretend(function (): void {
        dbUpdate([
            'state' => ['(SELECT password FROM users LIMIT 1)'],
            'status' => 'up',
            'error' => ['x' => ['y' => 'z']],
        ], 'table', '`id` = ?', [5]);
    });

    expect($queries)->toHaveCount(1);
    expect($queries[0]['query'])->toBe("UPDATE `table` set `state`=null,`status`='up',`error`=null WHERE `id` = 5");
});
