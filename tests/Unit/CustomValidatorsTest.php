<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

uses(LibreNMS\Tests\TestCase::class);

function assertRulePasses($value, string $rule): void
{
    $validator = Validator::make(['field' => $value], ['field' => $rule]);

    expect($validator->passes())->toBeTrue(sprintf('Expected rule "%s" to pass for value: %s', $rule, var_export($value, true)));
}

function assertRuleFails($value, string $rule): void
{
    $validator = Validator::make(['field' => $value], ['field' => $rule]);

    expect($validator->passes())->toBeFalse(sprintf('Expected rule "%s" to fail for value: %s', $rule, var_export($value, true)));
    expect($validator->errors()->toArray())->toHaveKey('field');
}

test('alpha space validation', function (): void {
    assertRulePasses('Alpha 123_', 'alpha_space');
    assertRulePasses(' spaced words ', 'alpha_space');
    assertRulePasses('umlaut ok', 'alpha_space');
    assertRulePasses('', 'alpha_space');

    assertRuleFails('dash-separated', 'alpha_space');
    assertRuleFails('punctuation!', 'alpha_space');
});

test('ip or hostname validation', function (): void {
    assertRulePasses('192.168.1.1', 'ip_or_hostname');
    assertRulePasses('192.168.1.1/24', 'ip_or_hostname');
    assertRulePasses('2001:db8::1', 'ip_or_hostname');
    assertRulePasses('2001:db8::1/64', 'ip_or_hostname');
    assertRulePasses('example.com', 'ip_or_hostname');

    //        $this->assertRuleFails('999.999.999.999', 'ip_or_hostname'); Unsure about this case
    assertRuleFails('exa mple.com', 'ip_or_hostname');
    assertRuleFails('example.com/24', 'ip_or_hostname');
});

test('is regex validation', function (): void {
    assertRulePasses('/[a-z]+/', 'is_regex');
    assertRulePasses('/^[a-z0-9_-]+$/i', 'is_regex');
    assertRulePasses('~^\\w+\\s+\\d+$~', 'is_regex');
    assertRulePasses('#^foo\\#bar$#', 'is_regex');
    assertRulePasses('', 'is_regex');

    assertRuleFails('/[a-z+/', 'is_regex');
    assertRuleFails('not-a-regex', 'is_regex');
    assertRuleFails('/[a-z]+/z', 'is_regex');
});

test('zero or exists validation', function (): void {
    // use simple in-memory db, full db testing is much slower (20s or more)
    $original_connection = config('database.default');
    config([
        'database.connections.validator_testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'database.default' => 'validator_testing',
    ]);

    DB::purge('validator_testing');
    DB::connection('validator_testing')->getPdo();

    Schema::connection('validator_testing')->create('validator_items', function (Blueprint $table): void {
        $table->increments('id');
    });

    try {
        $row_id = DB::table('validator_items')->insertGetId(['id' => null]);

        assertRulePasses(0, 'zero_or_exists:validator_items,id');
        assertRulePasses('0', 'zero_or_exists:validator_items,id');
        assertRulePasses($row_id, 'zero_or_exists:validator_items,id');

        assertRuleFails($row_id + 1, 'zero_or_exists:validator_items,id');
    } finally {
        config(['database.default' => $original_connection]);
        DB::disconnect('validator_testing');
    }
});

test('url or xml validation', function (): void {
    assertRulePasses('https://example.com/path?x=1', 'url_or_xml');
    assertRulePasses('<root><child/></root>', 'url_or_xml');

    assertRuleFails('not a url or xml', 'url_or_xml');
    assertRuleFails('<root>', 'url_or_xml');
    assertRuleFails(123, 'url_or_xml');
});

test('array keys not empty validation', function (): void {
    assertRulePasses(['foo' => 'bar'], 'array_keys_not_empty');
    assertRulePasses(['0' => 'bar', 1 => 'baz'], 'array_keys_not_empty');

    assertRuleFails(['' => 'bar'], 'array_keys_not_empty');
    assertRuleFails([' ' => 'bar'], 'array_keys_not_empty');
    assertRuleFails('not-an-array', 'array_keys_not_empty');
});

test('date or relative validation', function (): void {
    assertRulePasses('123456789', 'date_or_relative');
    assertRulePasses('1699999999', 'date_or_relative');
    assertRulePasses('1234567890123', 'date_or_relative');
    assertRulePasses('2h', 'date_or_relative');
    assertRulePasses('-3d', 'date_or_relative');
    assertRulePasses('+4w', 'date_or_relative');
    assertRulePasses('2023-05-01', 'date_or_relative');

    assertRuleFails('10z', 'date_or_relative');
    assertRuleFails('12345678901234', 'date_or_relative');
    assertRuleFails('not-a-date', 'date_or_relative');
});
