<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Polling\Method\Config\SnmpConfig;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData;
use LibreNMS\Scan\SnmpProbe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;


uses(\LibreNMS\Tests\Traits\FakesNetworkTools::class);

test('preference', function (array $communities, ?int $expected) {
    $this->fakeSnmpget();
    $probe = new SnmpProbe('192.0.2.1', $this->fakeSnmpConfigs($communities));

    while (! $probe->settled()) {
        usleep(1000);
    }

    expect($probe->secretId())->toBe($expected);
})->with('credentialProvider');

/**
 * @return array<string, array{array<int, string>, ?int}>
 */
dataset('credentialProvider', function () {
    return [
        'waits for a slower preferred credential' => [[10 => '0-1', 1 => '0.3-0', 2 => '0-0'], 1],
        'skips credentials without a reply' => [[1 => '0.2-1', 2 => '0-0'], 2],
        'an error response is a reply' => [[1 => '0-2'], 1],
        'no reply' => [[1 => '0-1', 2 => '0-1'], null],
    ];
});

test('version', function () {
    $this->fakeSnmpget();
    $defaults = (new SnmpPollingMethod)->defaults();
    $probe = new SnmpProbe('192.0.2.1', [
        [7, SnmpConfig::make($defaults, new SnmpCommunitySecretData(community: '0-1'))],
        [7, SnmpConfig::make(['version' => 'v1'] + $defaults, new SnmpCommunitySecretData(community: '0-0'))],
    ]);

    while (! $probe->settled()) {
        usleep(1000);
    }

    expect($probe->secretId())->toBe(7);
    expect($probe->version())->toBe('v1');
});

test('no credentials', function () {
    $probe = new SnmpProbe('192.0.2.1', []);

    expect($probe->settled())->toBeTrue();
    expect($probe->secretId())->toBeNull();
});