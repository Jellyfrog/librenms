<?php

use LibreNMS\Scan\PingBatch;

uses(\LibreNMS\Tests\TestCase::class, \LibreNMS\Tests\Traits\FakesNetworkTools::class);

test('Reads each result once, with one fping process for the batch', function (): void {
    $log = $this->fakeFping(['192.0.2.1', '2001:db8::1']);
    $batch = new PingBatch(['192.0.2.1', '192.0.2.2', '2001:db8::1']);

    expect(waitForPing($batch, '192.0.2.1'))->toBeTrue();
    expect(waitForPing($batch, '192.0.2.2'))->toBeFalse();
    expect(waitForPing($batch, '2001:db8::1'))->toBeTrue();

    // pending until fping finishes, then not alive
    expect(waitForPing($batch, '192.0.2.1'))->toBeFalse('a result is read once');
    expect(waitForPing($batch, '192.0.2.99'))->toBeFalse('an address not in the batch is not alive');
    expect(file($log, FILE_IGNORE_NEW_LINES))->toBe(['3']);
});

function waitForPing(PingBatch $batch, string $ip): bool
{
    while (($result = $batch->result($ip)) === null) {
        $batch->poll();
        usleep(1000);
    }

    return $result;
}