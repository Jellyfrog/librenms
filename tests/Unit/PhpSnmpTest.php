<?php

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Data\Source\Snmp\NetSnmpOptions;
use LibreNMS\Data\Source\Snmp\PhpSnmp;
use LibreNMS\Data\Source\Snmp\SnmpQueryOptions;
use LibreNMS\Polling\Method\Config\SnmpConfig;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData;
use LibreNMS\Polling\Secrets\Data\SnmpV3SecretData;

/**
 * @return array{version: string, transport: string, port: int, timeout: int|float, retries: int, max_repeaters: int, max_oid: int, bulk: bool, context: ?string, port_association_mode: string}
 */
function backendDefaults(): array
{
    return ['transport' => 'udp', 'bulk' => true] + (new SnmpPollingMethod)->defaults();
}

function assertBackendUnavailable(): void
{
    $config = SnmpConfig::make(backendDefaults(), new SnmpCommunitySecretData(community: 'public'));

    expect((new PhpSnmp)->buildSnmp('127.0.0.1', $config, new SnmpQueryOptions))->toBeNull();
}

test('build snmp applies max oids', function () {
    if (! class_exists(\SNMP::class) || ! function_exists('snmp_init_mib')) {
        assertBackendUnavailable();

        return;
    }

    $backend = new PhpSnmp;
    $defaults = backendDefaults();

    foreach ([
        ['v2c', new SnmpCommunitySecretData(community: 'public')],
        ['v3', new SnmpV3SecretData(authname: 'alice')],
    ] as [$version, $secret]) {
        foreach ([-1 => 1, 0 => 1, 1 => 1, 4 => 4, 20 => 20] as $configured => $expected) {
            $config = SnmpConfig::make(['version' => $version, 'max_oid' => $configured] + $defaults, $secret);
            $snmp = $backend->buildSnmp('127.0.0.1', $config, new SnmpQueryOptions);

            expect($snmp)->not->toBeNull();
            expect($snmp->max_oids)->toBe($expected, "$version max_oid=$configured");
        }
    }
});

test('backend eligibility', function () {
    if (! class_exists(\SNMP::class) || ! function_exists('snmp_init_mib')) {
        assertBackendUnavailable();

        return;
    }

    $method = new \ReflectionMethod(PhpSnmp::class, 'worksFor');
    $backend = new PhpSnmp;
    $defaults = backendDefaults();
    $secret = new SnmpCommunitySecretData(community: 'public');
    $config = SnmpConfig::make($defaults, $secret);

    expect($method->invoke($backend, $config, new SnmpQueryOptions))->toBeTrue();
    expect($method->invoke($backend, SnmpConfig::make(['version' => 'v1'] + $defaults, $secret), new SnmpQueryOptions))->toBeFalse();
    expect($method->invoke($backend, SnmpConfig::make(['transport' => 'tcp'] + $defaults, $secret), new SnmpQueryOptions))->toBeFalse();
    expect($method->invoke($backend, SnmpConfig::make(['bulk' => false] + $defaults, $secret), new SnmpQueryOptions))->toBeFalse();
    expect($method->invoke($backend, $config, new SnmpQueryOptions(allowBulk: false)))->toBeFalse();
    expect($method->invoke($backend, $config, new SnmpQueryOptions(applyDisplayHints: false)))->toBeFalse();
});

test('community and username', function () {
    $method = new \ReflectionMethod(PhpSnmp::class, 'communityOrUsername');
    $backend = new PhpSnmp;
    $defaults = backendDefaults();

    $v3 = SnmpConfig::make($defaults, new SnmpV3SecretData(authname: 'alice'));
    $v3Default = SnmpConfig::make($defaults, new SnmpV3SecretData);
    $v2 = SnmpConfig::make($defaults, new SnmpCommunitySecretData(community: 'private'));
    $v1 = SnmpConfig::make(['version' => 'v1'] + $defaults, new SnmpCommunitySecretData(community: 'private'));
    $v2Default = SnmpConfig::make($defaults, new SnmpCommunitySecretData(community: ''));

    expect($method->invoke($backend, $v3, ''))->toBe('alice');
    expect($method->invoke($backend, $v3, 'vrf1'))->toBe('alice');
    expect($method->invoke($backend, $v3Default, 'vrf1'))->toBe('root');
    expect($method->invoke($backend, $v2, ''))->toBe('private');
    expect($method->invoke($backend, $v2, 'vrf1'))->toBe('private@vrf1');
    expect($method->invoke($backend, $v1, 'vrf1'))->toBe('private@vrf1');
    expect($method->invoke($backend, $v2Default, ''))->toBe('public');
});

test('v3 security levels and context', function () {
    if (! class_exists(\SNMP::class)) {
        assertBackendUnavailable();

        return;
    }

    $method = new \ReflectionMethod(PhpSnmp::class, 'setSecurityOptions');
    $backend = new PhpSnmp;
    $defaults = backendDefaults();

    foreach ([
        ['authPriv', 'SHA', 'AES', ['authPriv', 'SHA', 'authpass', 'AES', 'privpass', 'vrf1']],
        ['authPriv', 'SHA-256', 'DES', ['authPriv', 'SHA256', 'authpass', 'DES', 'privpass', 'vrf1']],
        ['authPriv', 'SHA-512', 'AES', ['authPriv', 'SHA512', 'authpass', 'AES', 'privpass', 'vrf1']],
        ['authPriv', 'SHA', 'AES-192', ['authPriv', 'SHA', 'authpass', 'AES192', 'privpass', 'vrf1']],
        ['authPriv', 'SHA', 'AES-256', ['authPriv', 'SHA', 'authpass', 'AES256', 'privpass', 'vrf1']],
        ['authPriv', 'SHA', 'AES-192-C', ['authPriv', 'SHA', 'authpass', 'AES192C', 'privpass', 'vrf1']],
        ['authPriv', 'SHA', 'AES-256-C', ['authPriv', 'SHA', 'authpass', 'AES256C', 'privpass', 'vrf1']],
        ['authPriv', 'SHA', 'aes-256-c', ['authPriv', 'SHA', 'authpass', 'AES256C', 'privpass', 'vrf1']],
        ['authNoPriv', 'SHA-256', 'AES', ['authNoPriv', 'SHA256', 'authpass', '', '', 'vrf1']],
        ['authNoPriv', 'SHA-512', 'AES', ['authNoPriv', 'SHA512', 'authpass', '', '', 'vrf1']],
        ['authNoPriv', 'sha-256', 'AES', ['authNoPriv', 'SHA256', 'authpass', '', '', 'vrf1']],
        ['authNoPriv', 'MD5', 'AES', ['authNoPriv', 'MD5', 'authpass', '', '', 'vrf1']],
        ['noAuthNoPriv', 'SHA-224', 'AES-256', ['noAuthNoPriv', '', '', '', '', 'vrf1']],
    ] as [$level, $authalgo, $cryptoalgo, $expected]) {
        $config = SnmpConfig::make($defaults, new SnmpV3SecretData(
            authname: 'alice',
            authlevel: $level,
            authpass: 'authpass',
            authalgo: $authalgo,
            cryptoalgo: $cryptoalgo,
            cryptopass: 'privpass',
        ));

        $snmp = $this->getMockBuilder(\SNMP::class)->disableOriginalConstructor()->onlyMethods(['setSecurity'])->getMock();
        $snmp->expects($this->once())->method('setSecurity')->with(...$expected)->willReturn(true);
        expect($method->invoke($backend, $snmp, $config, 'vrf1'))->toBeTrue();
    }
});

test('extended privacy algorithms use supported backend', function () {
    if (! class_exists(\SNMP::class) || ! function_exists('snmp_init_mib')) {
        assertBackendUnavailable();

        return;
    }

    $method = new \ReflectionMethod(PhpSnmp::class, 'worksFor');

    // Extended AES names depend on the installed SNMP extension.
    $setSecurity = new \ReflectionMethod(\SNMP::class, 'setSecurity');
    $backend = new PhpSnmp;
    $defaults = backendDefaults();

    foreach ([
        'AES-192' => 'AES192',
        'AES-256' => 'AES256',
        'AES-192-C' => 'AES192C',
        'AES-256-C' => 'AES256C',
    ] as $configured => $phpName) {
        $secret = new SnmpV3SecretData(
            authname: 'alice',
            authlevel: 'authPriv',
            authalgo: 'SHA',
            authpass: 'authpass',
            cryptoalgo: $configured,
            cryptopass: 'privpass',
        );
        $config = SnmpConfig::make($defaults, $secret);
        $snmp = new \SNMP(\SNMP::VERSION_3, 'localhost', 'alice');
        try {
            $supported = $setSecurity->invoke($snmp, 'authPriv', 'SHA', 'authpass', $phpName, 'privpass');
        } catch (\ValueError) {
            $supported = false;
        }

        expect($method->invoke($backend, $config, new SnmpQueryOptions))->toBe($supported, $configured);
        expect((new NetSnmpOptions)->buildAuth($config))->toBe(['-v3', '-l', 'authPriv', '-x', $configured, '-X', 'privpass', '-a', 'SHA', '-A', 'authpass', '-u', 'alice']);
    }
});

test('unsupported v3 algorithms fall back', function () {
    if (! class_exists(\SNMP::class) || ! function_exists('snmp_init_mib')) {
        assertBackendUnavailable();

        return;
    }

    $method = new \ReflectionMethod(PhpSnmp::class, 'worksFor');
    $backend = new PhpSnmp;
    $defaults = backendDefaults();

    $valid = SnmpConfig::make($defaults, new SnmpV3SecretData(authname: 'alice'));
    expect($method->invoke($backend, $valid, new SnmpQueryOptions))->toBeTrue();

    foreach ([
        new SnmpV3SecretData(authlevel: 'authNoPriv', authalgo: 'unsupported', authpass: 'authpass'),
        new SnmpV3SecretData(authlevel: 'authPriv', authalgo: 'SHA', authpass: 'authpass', cryptoalgo: 'unsupported', cryptopass: 'privpass'),
    ] as $secret) {
        expect($method->invoke($backend, SnmpConfig::make($defaults, $secret), new SnmpQueryOptions))->toBeFalse();
    }
});

test('failed security setup falls back', function () {
    if (! class_exists(\SNMP::class) || ! function_exists('snmp_init_mib')) {
        assertBackendUnavailable();

        return;
    }

    $config = SnmpConfig::make(backendDefaults(), new SnmpV3SecretData(
        authlevel: 'authNoPriv',
        authalgo: 'SHA',
        authpass: 'short',
    ));
    $warnings = [];
    set_error_handler(function (int $severity, string $message) use (&$warnings): bool {
        $warnings[] = $message;

        return true;
    }, E_WARNING);
    try {
        $method = new \ReflectionMethod(PhpSnmp::class, 'worksFor');
        $result = $method->invoke(new PhpSnmp, $config, new SnmpQueryOptions);
    } finally {
        restore_error_handler();
    }

    expect($result)->toBeFalse();
    expect($warnings)->not->toBeEmpty();
});
