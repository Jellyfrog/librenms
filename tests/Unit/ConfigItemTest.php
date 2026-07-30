<?php

use LibreNMS\Util\DynamicConfigItem;

uses(LibreNMS\Tests\TestCase::class);

test('executable validation', function (): void {
    $executableType = new DynamicConfigItem('testExecutable', [
        'type' => 'executable',
    ]);

    $lnms_path = base_path('lnms');

    // this should be executable
    expect($executableType->checkValue($lnms_path))->toBeTrue();
    expect($executableType->checkValue(base_path() . '/app/../lnms'))->toBeTrue();
    //check path manipulation
    expect($executableType->checkValue('/'))->toBeFalse();
    expect($executableType->checkValue(base_path('LICENSE.txt')))->toBeFalse();

    // non-executable file
    $bad_characters = ['`', ';', '#', '$', '|', '&', '\'', '"', '>', '<', '(', ' '];
    foreach ($bad_characters as $bad) {
        expect($executableType->checkValue($lnms_path . $bad))->toBeFalse();
    }
});

test('directory validation', function (): void {
    $executableType = new DynamicConfigItem('testDirectory', [
        'type' => 'directory',
    ]);

    expect($executableType->checkValue(__DIR__))->toBeTrue();
    expect($executableType->checkValue(__DIR__ . '/../' . basename(__DIR__)))->toBeTrue();
    //check path manipulation
    expect($executableType->checkValue('/'))->toBeTrue();
    expect($executableType->checkValue(__FILE__))->toBeFalse();
    // check file
    expect($executableType->checkValue(base_path('LICENSE.txt')))->toBeFalse();

    // check file
    $bad_characters = ['`', ';', '#', '$', '|', '&', '\'', '"', '>', '<', '(', ' '];
    foreach ($bad_characters as $bad) {
        expect($executableType->checkValue(__DIR__ . $bad))->toBeFalse();
    }
});

test('array sub keyed validation', function (): void {
    $arraySubKeyedType = new DynamicConfigItem('testArray', [
        'type' => 'array-sub-keyed',
    ]);

    expect($arraySubKeyedType->checkValue(['foo' => ['bar']]))->toBeTrue();
    expect($arraySubKeyedType->checkValue(['0' => ['bar']]))->toBeTrue();
    expect($arraySubKeyedType->checkValue([0 => ['bar']]))->toBeTrue();
    expect($arraySubKeyedType->checkValue(['foo' => []]))->toBeTrue();
    expect($arraySubKeyedType->checkValue(['foo' => ['bar' => []]]))->toBeTrue();

    expect($arraySubKeyedType->checkValue([true => []]))->toBeTrue();
    // PHP converts it to [1 => []]
    expect($arraySubKeyedType->checkValue([false => []]))->toBeTrue();

    // PHP converts it to [[]]
    expect($arraySubKeyedType->checkValue(['foo' => 'bar']))->toBeFalse();
    expect($arraySubKeyedType->checkValue(['foo' => null]))->toBeFalse();
    expect($arraySubKeyedType->checkValue(['foo' => false]))->toBeFalse();
    expect($arraySubKeyedType->checkValue(['' => []]))->toBeFalse();
    expect($arraySubKeyedType->checkValue([' ' => []]))->toBeFalse();
    expect($arraySubKeyedType->checkValue([null => []]))->toBeFalse();
});

test('array keys not empty validation', function (): void {
    $array_keys_not_empty = new DynamicConfigItem('testArray', [
        'type' => 'array-sub-keyed',
        'validate' => [
            'value' => 'array_keys_not_empty',
            'value.*' => 'array_keys_not_empty',
        ],
    ]);

    expect($array_keys_not_empty->checkValue(['foo' => ['bar']]))->toBeTrue();
    expect($array_keys_not_empty->checkValue(['0' => ['bar']]))->toBeTrue();
    expect($array_keys_not_empty->checkValue([0 => ['bar']]))->toBeTrue();
    expect($array_keys_not_empty->checkValue(['foo' => []]))->toBeTrue();
    expect($array_keys_not_empty->checkValue(['foo' => ['bar' => []]]))->toBeTrue();

    expect($array_keys_not_empty->checkValue([true => []]))->toBeTrue();
    // PHP converts it to [1 => []]
    expect($array_keys_not_empty->checkValue([false => []]))->toBeTrue();

    // PHP converts it to [[]]
    expect($array_keys_not_empty->checkValue(['foo' => 'bar']))->toBeFalse();
    expect($array_keys_not_empty->checkValue(['foo' => ['' => []]]))->toBeFalse();
    expect($array_keys_not_empty->checkValue(['foo' => null]))->toBeFalse();
    expect($array_keys_not_empty->checkValue(['foo' => false]))->toBeFalse();
    expect($array_keys_not_empty->checkValue(['' => []]))->toBeFalse();
    expect($array_keys_not_empty->checkValue([' ' => []]))->toBeFalse();
    expect($array_keys_not_empty->checkValue([null => []]))->toBeFalse();
});
