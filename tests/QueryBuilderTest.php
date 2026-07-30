<?php

/**
 * QueryBuilderTest.php
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use LibreNMS\Alerting\QueryBuilderFluentParser;

uses(\LibreNMS\Tests\TestCase::class);

const QUERY_BUILDER_DATA_FILE = 'tests/data/misc/querybuilder.json';

function loadQueryData(): array
{
    $base = realpath(__DIR__ . '/..');
    $data = file_get_contents("$base/" . QUERY_BUILDER_DATA_FILE);

    return json_decode($data, true);
}

dataset('query_builder_data', fn () => loadQueryData());

test('has query data', function () {
    expect(loadQueryData())->not->toBeEmpty('Could not load query builder test data from ' . QUERY_BUILDER_DATA_FILE);
});

test('query conversion', function ($legacy, $builder, $display, $sql, $query) {
    $qb = QueryBuilderFluentParser::fromJson($builder);
    expect($qb->toSql(false))->toEqual($display);
    expect($qb->toSql())->toEqual($sql);

    $qbq = $qb->toQuery();
    expect($qbq->toSql())->toEqual($query[0], 'Fluent SQL does not match');
    expect($qbq->getBindings())->toEqual($query[1], 'Fluent bindings do not match');
})->with('query_builder_data');