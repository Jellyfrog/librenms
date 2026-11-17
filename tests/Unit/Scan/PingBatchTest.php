<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Scan\PingBatch;
use PHPUnit\Framework\Attributes\TestDox;


uses(\LibreNMS\Tests\Traits\FakesNetworkTools::class);

test('results', function () {
    $log = $this->fakeFping(['192.0.2.1', '2001:db8::1']);
    $batch = new PingBatch(['192.0.2.1', '192.0.2.2', '2001:db8::1']);

    expect(wait($batch, '192.0.2.1'))->toBeTrue();
    expect(wait($batch, '192.0.2.2'))->toBeFalse();
    expect(wait($batch, '2001:db8::1'))->toBeTrue();

    // pending until fping finishes, then not alive
    expect(wait($batch, '192.0.2.1'))->toBeFalse('a result is read once');
    expect(wait($batch, '192.0.2.99'))->toBeFalse('an address not in the batch is not alive');
    expect(file($log, FILE_IGNORE_NEW_LINES))->toBe(['3']);
});

function wait(PingBatch $batch, string $ip): bool
{
    while (($result = $batch->result($ip)) === null) {
        $batch->poll();
        usleep(1000);
    }

    return $result;
}