<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Validations\Snmp;
use LibreNMS\Validator;
use PHPUnit\Framework\Attributes\DataProvider;


test('net snmp version', function (string $version, bool $warns) {
    $validator = Mockery::mock(Validator::class);
    if ($warns) {
        $validator->shouldReceive('warn')->once()->withArgs(fn (string $message, string $fix) => str_starts_with($message, "net-snmp $version is older than 5.8.")
            && $fix === 'Update net-snmp to version 5.8 or newer.');
    } else {
        $validator->shouldNotReceive('warn');
    }

    (new Snmp)->checkNetSnmpVersion($validator, $version);
})->with('versions');

/**
 * @return array<string, array{string, bool}>
 */
dataset('versions', function () {
    return [
        'not installed' => ['', false],
        '5.7.3' => ['5.7.3', true],
        '5.7.2.1' => ['5.7.2.1', true],
        '5.8' => ['5.8', false],
        '5.9.5.2' => ['5.9.5.2', false],
    ];
});
