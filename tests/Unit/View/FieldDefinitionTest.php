<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\View\FieldSchema\FieldDefinition;
use Illuminate\Validation\Rules\In;

test('default and placeholder', function () {
    $withDefault = FieldDefinition::make('port', 'number')->default(161);
    expect($withDefault->getDefault())->toBe(161);
    expect($withDefault->getPlaceholder())->toBe('161');

    $withCallableDefault = FieldDefinition::make('timeout', 'number')->default(fn (): int => 5);
    expect($withCallableDefault->getDefault())->toBe(5);

    // a string default that is also a function name is not called
    expect(FieldDefinition::make('authalgo', 'select')->options(['SHA' => 'SHA', 'MD5' => 'MD5'])->default('MD5')->getDefault())->toBe('MD5');

    $withPlaceholder = FieldDefinition::make('hostname', 'text')->placeholder('device hostname');
    expect($withPlaceholder->getDefault())->toBeNull();
    expect($withPlaceholder->getPlaceholder())->toBe('device hostname');

    // a select defaults to its first option
    expect(FieldDefinition::make('choice', 'select')->options(['a' => 'A', 'b' => 'B'])->getDefault())->toBe('a');
});

test('rules are generated from the field', function () {
    expect(FieldDefinition::make('retries', 'number')->min(1)->max(10)->getRules())->toBe(['nullable', 'integer', 'min:1', 'max:10']);
    expect(FieldDefinition::make('timeout', 'number')->min(0.1)->cast('float')->getRules())->toBe(['nullable', 'numeric', 'min:0.1']);
    expect(FieldDefinition::make('hostname', 'text')->getRules())->toBe(['nullable', 'string']);

    $selectRules = FieldDefinition::make('transport', 'select')->options(['udp' => 'UDP', 'tcp' => 'TCP'])->getRules();
    expect($selectRules[0])->toBe('nullable');
    expect($selectRules[1])->toBeInstanceOf(In::class);
    expect((string) $selectRules[1])->toBe('in:"udp","tcp"');

    // explicit rules replace the generated ones
    expect(FieldDefinition::make('port', 'number')->min(1)->rules(['required'])->getRules())->toBe(['required']);
});

test('cast value', function () {
    expect(FieldDefinition::make('port', 'number')->castValue('161'))->toBe(161);
    expect(FieldDefinition::make('timeout', 'number')->cast('float')->castValue('2.5'))->toBe(2.5);
    expect(FieldDefinition::make('transport', 'select')->castValue('udp'))->toBe('udp');
    expect(FieldDefinition::make('port', 'number')->castValue(null))->toBeNull();

    $bool = FieldDefinition::make('bulk', 'select')->cast('bool');
    expect($bool->castValue('1'))->toBeTrue();
    expect($bool->castValue('false'))->toBeFalse();
    expect($bool->castValue('maybe'))->toBeNull();
});

test('visible if expression', function () {
    $field = FieldDefinition::make('community', 'password')->visibleIf(['version' => ['$in' => ['v1', 'v2c']], 'enabled' => true]);

    expect($field->buildVisibleIfExpression())->toBe('["v1","v2c"].includes(formData["version"]) && formData["enabled"] === true');
    expect(FieldDefinition::make('version', 'select')->buildVisibleIfExpression())->toBeNull();
});
