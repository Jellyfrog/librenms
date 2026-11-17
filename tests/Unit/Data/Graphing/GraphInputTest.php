<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\Graphing\GraphInput;

test('it parses strict types', function () {
    $vars = new GraphInput(['id' => '12', 'list' => '1,2', 'arr' => [1], 'f' => '0.5', 'yes' => 'yes', 'off' => 'off', 'bad' => 'maybe', 'n' => 5]);

    expect($vars->int('id'))->toBe(12);
    expect($vars->int('list'))->toBeNull();
    expect($vars->int('arr'))->toBeNull();
    expect($vars->int('missing'))->toBeNull();
    expect($vars->float('f'))->toBe(0.5);
    expect($vars->bool('yes'))->toBeTrue();
    expect($vars->bool('off'))->toBeFalse();
    expect($vars->bool('bad'))->toBeNull();
    expect($vars->string('n'))->toBe('5');
    expect($vars->string('arr'))->toBeNull();
});
