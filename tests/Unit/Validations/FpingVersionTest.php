<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Validations\Programs;
use LibreNMS\Validator;
use PHPUnit\Framework\Attributes\DataProvider;


test('fping version', function (string $version, bool $warns) {
    $validator = Mockery::mock(Validator::class);
    if ($warns) {
        $validator->shouldReceive('warn')->once()->withArgs(fn (string $message, string $fix) => str_starts_with($message, "fping $version is older than 4.0 ")
            && $fix === 'Update fping to version 4.0 or newer.');
    } else {
        $validator->shouldNotReceive('warn');
    }

    (new Programs)->checkFpingVersion($validator, $version);
})->with('versions');

/**
 * @return array<string, array{string, bool}>
 */
dataset('versions', function () {
    return [
        'unknown' => ['', false],
        '3.16' => ['3.16', true],
        '4.0' => ['4.0', false],
        '5.5' => ['5.5', false],
    ];
});
