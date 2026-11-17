<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\AlertFault;
use App\Models\AlertLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;


/**
 * @return array<string, array{class-string<AlertFault|AlertLog>}>
 */
dataset('models', function () {
    return [
        'faults' => [AlertFault::class],
        'alert log' => [AlertLog::class],
    ];
});

test('every filter field is applied', function (string $model) {
    $base = $model::query()->toSql();

    foreach ($model::filterFieldDefinitions() as $field) {
        $sql = $model::query()->applyFilters([$field['key'] => ['eq' => '1']])->toSql();
        $this->assertNotEquals($base, $sql, "Filter field {$field['key']} is shown but not filterable");
    }
})->with('models');

test('select options are json objects', function (string $model) {
    // the filter bar uses each entry of a JSON list as both value and label
    foreach ($model::filterFieldDefinitions() as $field) {
        if (isset($field['options'])) {
            expect(json_encode($field['options']))->toStartWith('{', "{$field['key']} options must encode as an object");
        }
    }
})->with('models');

test('device pages leave out device filters', function (string $model) {
    expect(array_column($model::filterFieldDefinitions(), 'key'))->toContain('device_id');

    $keys = array_column($model::filterFieldDefinitions(1), 'key');
    expect($keys)->toContain('rule_id');
    expect(array_intersect(['device_id', 'device.location_id', 'device.groups.id'], $keys))->toBeEmpty();
})->with('models');

test('state filter uses names', function () {
    expect(AlertLog::query()->applyFilters(['state' => ['eq' => 'alerted']])->getBindings())->toBe([1]);
    expect(AlertLog::query()->applyFilters(['state' => ['in' => 'ok,better']])->getBindings())->toBe([0, 4]);
    expect(AlertFault::query()->applyFilters(['state' => ['neq' => 'acknowledged']])->getBindings())->toBe([2]);
    expect(AlertFault::query()->applyFilters(['state' => ['eq' => '3']])->getBindings())->toBe([3], 'numeric states from older urls');

    // a label or unknown name must not be cast to 0 (Clear) by the database
    expect(AlertLog::query()->applyFilters(['state' => ['eq' => 'Alert']])->getBindings())->toBe([-1]);
    expect(AlertLog::filterStateName(0))->toBe('ok');
    expect(AlertFault::filterStateName(0))->toBeNull();
});

test('state none and any', function (string $model) {
    // state is never empty, '' would be cast to 0 (Clear)
    expect($model::query()->applyFilters(['state' => ['is_empty' => null]])->toSql())->toEndWith('where 0 = 1');
    expect($model::query()->applyFilters(['state' => ['is_not_empty' => null]])->toSql())->toEqual($model::query()->toSql());
})->with('models');

test('unlisted fields are ignored', function (string $model) {
    $base = $model::query()->toSql();

    foreach (['open', 'alert_faults.open', 'details', 'device.community', 'device.authpass', 'rule.builder', 'state`=1 or 1=1 -- '] as $field) {
        expect($model::query()->applyFilters([$field => ['eq' => '0']])->toSql())->toEqual($base, "$field should not be filterable");
    }
})->with('models');

test('state is qualified', function () {
    // alert_log is joined to a grouped subquery that also has a state column
    $this->assertStringContainsString('`alert_log`.`state` = ?', AlertLog::query()->applyFilters(['state' => ['in' => 'ok,alerted']])->toSql());
    $this->assertStringContainsString('where not ((`alert_faults`.`state` = ?))', AlertFault::query()->applyFilters(['state' => ['neq' => 'acknowledged']])->toSql());
});

test('search matches rule and device', function (string $model) {
    $query = $model::query()->applyFilters(['search' => ['contains' => 'core']]);

    assertSql('exists (select * from `alert_rules` where', [], $query);
    assertSql('`name` like ?', [], $query);
    assertSql('or exists (select * from `devices` where', [], $query);
    assertSql('`hostname` like ?', [], $query);
    expect($query->getBindings())->toBe(['%core%', '%core%', '%core%', '%core%']);
})->with('models');

test('relation filters', function (string $model) {
    assertSql('`alert_rules`.`id` and `severity` in (?, ?))', ['warning', 'critical'], $model::query()->applyFilters(['rule.severity' => ['in' => 'warning,critical']]));
})->with('models');

/**
 * @return array<string, array{array<string, array<string, string|null>>, string, list<string>}>
 */
dataset('deviceIdFilters', function () {
    $groups = 'device_id` %s (select `device_id` from `device_group_device`';
    $locations = 'device_id` %s (select `device_id` from `devices` where `location_id` is not null';

    return [
        'group is' => [['device.groups.id' => ['eq' => '5']], sprintf($groups, 'in') . ' where `device_group_id` in (?))', ['5']],
        'group is any of' => [['device.groups.id' => ['in' => '5,6']], sprintf($groups, 'in') . ' where `device_group_id` in (?, ?))', ['5', '6']],
        'group is not' => [['device.groups.id' => ['neq' => '5']], sprintf($groups, 'not in') . ' where `device_group_id` in (?))', ['5']],
        'group none' => [['device.groups.id' => ['is_empty' => null]], sprintf($groups, 'not in') . ')', []],
        'group any' => [['device.groups.id' => ['is_not_empty' => null]], sprintf($groups, 'in') . ')', []],
        'location is' => [['device.location_id' => ['eq' => '3']], sprintf($locations, 'in') . ' and `location_id` in (?))', ['3']],
        'location is none of' => [['device.location_id' => ['not_in' => '3,4']], sprintf($locations, 'not in') . ' and `location_id` in (?, ?))', ['3', '4']],
        'location none' => [['device.location_id' => ['is_empty' => null]], sprintf($locations, 'not in') . ')', []],
    ];
});

test('device id filters', function (array $filter, string $expected, array $bindings) {
    foreach ([AlertFault::class, AlertLog::class] as $model) {
        $query = $model::query()->applyFilters($filter);
        expect($query->toSql())->toEndWith('.`' . $expected);
        $this->assertStringNotContainsString('exists', $query->toSql());
        expect($query->getBindings())->toBe($bindings);
    }
})->with('deviceIdFilters');

test('unreachable', function () {
    $unreachable = AlertFault::query()->applyFilters(['unreachable' => ['eq' => '1']])->toSql();
    $this->assertStringContainsString('exists (select * from `device_relationships`', $unreachable);
    $this->assertStringContainsString('and not exists (select * from `device_relationships` inner join `devices` as `parent_devices`', $unreachable);

    $reachable = AlertFault::query()->applyFilters(['unreachable' => ['eq' => '0']])->toSql();
    $this->assertStringContainsString('(not exists (select * from `device_relationships`', $reachable);
    $this->assertStringContainsString('or exists (select * from `device_relationships` inner join', $reachable);
});

/**
 * @return array<string, array{mixed, bool}>
 */
dataset('filterValues', function () {
    return [
        'string' => [['state' => ['eq' => '1']], true],
        'int' => [['state' => ['eq' => 1]], true],
        'null' => [['state' => ['is_empty' => null]], true],
        'comma list' => [['state' => ['in' => '1,2']], true],
        'flat list for set operator' => [['state' => ['in' => ['1', '2']]], true],
        'list for scalar operator' => [['state' => ['eq' => ['1', '2']]], false],
        'list for pattern operator' => [['search' => ['contains' => ['a']]], false],
        'nested list' => [['state' => ['in' => [['1']]]], false],
        'unknown operator' => [['state' => ['bogus' => '1']], false],
        'too long' => [['search' => ['contains' => str_repeat('a', 256)]], false],
    ];
});

test('filter validation', function (mixed $filter, bool $valid) {
    $validator = Validator::make(['filter' => $filter], AlertFault::filterValidationRules());

    expect($validator->passes())->toBe($valid, json_encode($validator->errors()->all()));
})->with('filterValues');

/**
 * @param  list<string>  $bindings
 * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
 */
function assertSql(string $expected, array $bindings, Builder $query): void
{
    $this->assertStringContainsString($expected, $query->toSql());
    foreach ($bindings as $binding) {
        expect($query->getBindings())->toContain($binding);
    }
}
