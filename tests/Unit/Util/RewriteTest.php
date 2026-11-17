<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Util\Rewrite;


test('bgp error code', function () {
    expect(Rewrite::bgpErrorCode(6, 2))->toBe('Cease - Administrative Shutdown');
    expect(Rewrite::bgpErrorCode('6', '2'))->toBe('Cease - Administrative Shutdown');
    expect(Rewrite::bgpErrorCode(4, 0))->toBe('Hold Timer Expired');
    expect(Rewrite::bgpErrorCode(4, 99))->toBe('Hold Timer Expired');
    expect(Rewrite::bgpErrorCode(99, 0))->toBe('Unknown');
    expect(Rewrite::bgpErrorCode('', ''))->toBe('Unknown');
});
