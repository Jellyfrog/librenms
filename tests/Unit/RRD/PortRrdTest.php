<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\RRD\PortRrd;

test('tune limits', function () {
    expect(PortRrd::tuneLimits(9999999))->toBe([]);

    $limits = PortRrd::tuneLimits(1000000000);
    expect(array_keys($limits))->toBe(PortRrd::COUNTERS);
    expect($limits['INOCTETS'])->toEqual(['max' => 125000000]);
});
