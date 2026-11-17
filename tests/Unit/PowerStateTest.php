<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Enum\PowerState;
use PHPUnit\Framework\Attributes\DataProvider;


test('parse', function (string $state, int $expected) {
    expect(PowerState::parse($state))->toBe($expected);
})->with('states');

/**
 * @return array<string, array{string, int}>
 */
dataset('states', function () {
    return [
        'vmware off' => ['poweredOff', PowerState::OFF],
        'vmware on' => ['poweredOn', PowerState::ON],
        'vmware suspended' => ['suspended', PowerState::SUSPENDED],
        'libvirt off' => ['shut off', PowerState::OFF],
        'libvirt on' => ['running', PowerState::ON],
        'libvirt paused' => ['paused', PowerState::SUSPENDED],
        'proxmox stopped' => ['stopped', PowerState::OFF],
        'unknown' => ['crashed', PowerState::UNKNOWN],
    ];
});
