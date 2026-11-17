<?php

/**
 * SnmprecSnmpBackendTest.php
 *
 * Regression and unit tests for tests/Mocks/SnmprecSnmpBackend.php:
 * prefix-overlap matching, full OID index suffix in walk output,
 * newline termination, array-based SnmpResponse creation, and numeric output
 * parity with real net-snmp.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 Josh Thomas-Ward
 * @author     Josh Thomas-Ward <josh.thomasward@pelagicai.com>
 */

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Data\Source\Snmp\SnmpQueryOptions;
use LibreNMS\Enum\SnmpOidOutput;
use LibreNMS\Polling\Method\Config\SnmpConfig;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData;
use LibreNMS\Tests\Mocks\SnmprecSnmpBackend;

uses(\LibreNMS\Tests\SnmpsimHelpers::class);

private const string FIXTURE = 'snmpquerymock_regression';
private const string BASE_OID = '1.3.6.1.2.1.2.2.1.2';

function makeBackend(): SnmprecSnmpBackend
{
    return new SnmprecSnmpBackend();
}

function makeConfig(): SnmpConfig
{
    return SnmpConfig::make((new SnmpPollingMethod)->defaults(), new SnmpCommunitySecretData(community: self::FIXTURE));
}

function makeNumericOptions(): SnmpQueryOptions
{
    $options = SnmpQueryOptions::quickPrint();
    $options->oidFormat = SnmpOidOutput::Numeric;

    return $options;
}

test('walk does not match numerically adjacent subtrees', function () {
    $values = makeBackend()
        ->walk('localhost', self::BASE_OID, makeConfig(), makeNumericOptions())
        ->values();

    // base OID is "1.3.6.1.2.1.2.2.1.2"; siblings "1.3.6.1.2.1.2.20.x" and
    // "1.3.6.1.2.1.2.21.x" share the numeric prefix without a dot boundary.
    $this->assertArrayNotHasKey('.1.3.6.1.2.1.2.20.1', $values);
    $this->assertArrayNotHasKey('.1.3.6.1.2.1.2.21.1', $values);
    expect($values)->not->toContain('sibling-subtree-20');
    expect($values)->not->toContain('sibling-subtree-21');
});

test('walk returns each row with full oid suffix', function () {
    $values = makeBackend()
        ->walk('localhost', self::BASE_OID, makeConfig(), makeNumericOptions())
        ->values();

    expect($values)->toBe([
        '.1.3.6.1.2.1.2.2.1.2.1' => 'eth0',
        '.1.3.6.1.2.1.2.2.1.2.2' => 'eth1',
        '.1.3.6.1.2.1.2.2.1.2.3' => 'eth2',
    ]);
});

test('direct backend get and walk', function () {
    $backend = makeBackend();
    $config = makeConfig();
    $options = makeNumericOptions();

    $walkResponse = $backend->walk('localhost', self::BASE_OID, $config, $options);
    expect($walkResponse->isValid())->toBeTrue();
    expect($walkResponse->values())->toBe([
        '.1.3.6.1.2.1.2.2.1.2.1' => 'eth0',
        '.1.3.6.1.2.1.2.2.1.2.2' => 'eth1',
        '.1.3.6.1.2.1.2.2.1.2.3' => 'eth2',
    ]);

    $getResponse = $backend->get('localhost', [self::BASE_OID . '.1', self::BASE_OID . '.2'], $config, $options);
    expect($getResponse->isValid())->toBeTrue();
    expect($getResponse->values())->toBe([
        '.1.3.6.1.2.1.2.2.1.2.1' => 'eth0',
        '.1.3.6.1.2.1.2.2.1.2.2' => 'eth1',
    ]);
});

test('numeric output matches real net snmp', function () {
    $this->requireSnmpsim();

    $mock = makeBackend();
    $real = resolve(SnmpBackendInterface::class);

    $target = $this->getSnmpsimIp() ?? '127.0.0.1';
    $config = SnmpConfig::make(
        [
            'port' => $this->getSnmpsimPort(),
            'timeout' => 3,
            'retries' => 0,
        ] + (new SnmpPollingMethod)->defaults(),
        new SnmpCommunitySecretData(
            community: self::FIXTURE,
        ),
    );
    $options = makeNumericOptions();

    expect($mock->walk($target, self::BASE_OID, $config, $options)->values())->toBe($real->walk($target, self::BASE_OID, $config, $options)->values(), 'mock walk output diverges from real net-snmp');
    expect($mock->get($target, [self::BASE_OID . '.1'], $config, $options)->values())->toBe($real->get($target, [self::BASE_OID . '.1'], $config, $options)->values(), 'mock get output diverges from real net-snmp');
    expect($mock->get($target, [self::BASE_OID . '.99'], $config, $options)->values())->toBe($real->get($target, [self::BASE_OID . '.99'], $config, $options)->values(), 'mock get output for a missing OID diverges from real net-snmp');
    expect($mock->next($target, [self::BASE_OID], $config, $options)->values())->toBe($real->next($target, [self::BASE_OID], $config, $options)->values(), 'mock getnext output diverges from real net-snmp');
});