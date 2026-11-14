<?php

namespace LibreNMS\Tests\Unit;

use LibreNMS\Data\Source\Snmp\PhpSnmp;
use LibreNMS\Data\Source\Snmp\SnmpQueryOptions;
use LibreNMS\Enum\SnmpOidOutput;
use LibreNMS\Polling\Method\Config\SnmpConfig;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData;
use LibreNMS\Tests\TestCase;

class PhpSnmpNativeTest extends TestCase
{
    private function config(array $overrides = []): SnmpConfig
    {
        $sim = getenv('SNMPSIM') ?: '';
        if (! PhpSnmp::supportsNative() || $sim === '') {
            $this->markTestSkipped('librenms-snmp with SNMP_VALUE_NATIVE and SNMPSIM required');
        }
        [, $port] = explode(':', $sim);

        return SnmpConfig::make(['transport' => 'udp', 'port' => (int) $port, 'timeout' => 1, 'retries' => 0] + $overrides + (new SnmpPollingMethod)->defaults(),
            new SnmpCommunitySecretData(community: 'phpsnmp_native'));
    }

    private function target(): string
    {
        return explode(':', (string) getenv('SNMPSIM'))[0];
    }

    public function testGetReturnsNativeTypes(): void
    {
        $config = $this->config();
        $options = new SnmpQueryOptions(oidFormat: SnmpOidOutput::Numeric, numericEnums: true);
        $values = (new PhpSnmp)->get($this->target(), [
            '.1.3.6.1.2.1.1.1.0', '.1.3.6.1.2.1.1.2.0', '.1.3.6.1.2.1.1.3.0', '.1.3.6.1.2.1.2.2.1.6.1',
            '.1.3.6.1.2.1.2.2.1.7.1', '.1.3.6.1.2.1.2.2.1.10.1', '.1.3.6.1.2.1.4.20.1.1.10.0.0.1',
            '.1.3.6.1.2.1.31.1.1.1.6.1', '.1.3.6.1.2.1.31.1.1.1.18.1',
        ], $config, $options)->values();

        $this->assertSame('native test', $values['.1.3.6.1.2.1.1.1.0']);
        $this->assertSame('.1.3.6.1.4.1.8072.3.2.10', $values['.1.3.6.1.2.1.1.2.0']);
        $this->assertSame(123456, $values['.1.3.6.1.2.1.1.3.0']);
        $this->assertSame("\x00\x1a\x2b\x3c\x4d\x5e", $values['.1.3.6.1.2.1.2.2.1.6.1']);
        $this->assertSame(1, $values['.1.3.6.1.2.1.2.2.1.7.1']);
        $this->assertSame(4294967295, $values['.1.3.6.1.2.1.2.2.1.10.1']);
        $this->assertSame('10.0.0.1', $values['.1.3.6.1.2.1.4.20.1.1.10.0.0.1']);
        $this->assertSame('18446744073709551615', $values['.1.3.6.1.2.1.31.1.1.1.6.1']);
        $this->assertArrayHasKey('.1.3.6.1.2.1.31.1.1.1.18.1', $values);
        $this->assertNull($values['.1.3.6.1.2.1.31.1.1.1.18.1']);
    }

    public function testEnumLabels(): void
    {
        $response = (new PhpSnmp)->get($this->target(), ['IF-MIB::ifAdminStatus.1'], $this->config(), new SnmpQueryOptions);

        $this->assertSame('up', $response->value());
    }

    public function testWalkTable(): void
    {
        $table = (new PhpSnmp)->walkTable($this->target(), 'IF-MIB::ifTable', $this->config(), new SnmpQueryOptions);

        $this->assertTrue($table->isValid());
        $this->assertSame([1], $table->rows()['1']['index']);
        $this->assertSame('up', $table->rows()['1']['ifAdminStatus']);
        $this->assertSame(1000000000, $table->rows()['1']['ifSpeed']);
    }

    public function testMissingInstanceIsMarkerNotWarning(): void
    {
        $response = (new PhpSnmp)->get($this->target(), ['.1.3.6.1.2.1.1.1.0', '.1.3.6.1.2.1.1.1.1'], $this->config(), new SnmpQueryOptions(oidFormat: SnmpOidOutput::Numeric));

        $this->assertSame(['.1.3.6.1.2.1.1.1.0' => 'native test'], $response->values());
        $this->assertSame(\Snmp\Varbind::NoSuchInstance, $response->rawValues()['.1.3.6.1.2.1.1.1.1']);
        $this->assertTrue($response->isValid(true));
    }

    public function testTimeoutThrowsIntoResponse(): void
    {
        $this->config(); // skips without librenms-snmp 0.0.2
        $response = (new PhpSnmp)->get('127.0.0.1', ['.1.3.6.1.2.1.1.1.0'],
            SnmpConfig::make(['transport' => 'udp', 'port' => 1, 'timeout' => 0.1, 'retries' => 0] + (new SnmpPollingMethod)->defaults(), new SnmpCommunitySecretData(community: 'x')),
            new SnmpQueryOptions);

        $this->assertSame(\SNMP::ERRNO_TIMEOUT, $response->errorCode);
        $this->assertTrue($response->hasConnectionError());
        $this->assertSame([], $response->values());
    }

    /**
     * getnext walk: a bulk response would arrive (or time out) as a whole, so no rows come before the delayed one
     */
    public function testWalkTimeoutKeepsRows(): void
    {
        $response = (new PhpSnmp)->walk($this->target(), '.1.3.6.1.2.1.88.1.1', $this->config(['bulk' => false]), new SnmpQueryOptions(oidFormat: SnmpOidOutput::Numeric));

        $this->assertSame(['.1.3.6.1.2.1.88.1.1.1' => 1, '.1.3.6.1.2.1.88.1.1.2' => 2], $response->values());
        $this->assertSame(\SNMP::ERRNO_TIMEOUT, $response->errorCode);
        $this->assertTrue($response->hasConnectionError());
    }
}
