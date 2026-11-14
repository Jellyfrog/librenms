<?php

namespace LibreNMS\Tests\Unit;

use LibreNMS\Data\Source\Snmp\PhpSnmp;
use LibreNMS\Data\Source\Snmp\PhpSnmpSessions;
use LibreNMS\Data\Source\Snmp\SnmpQueryOptions;
use LibreNMS\Polling\Method\Config\SnmpConfig;
use LibreNMS\Polling\Method\Methods\SnmpPollingMethod;
use LibreNMS\Polling\Secrets\Data\SnmpCommunitySecretData;
use LibreNMS\Tests\TestCase;

class PhpSnmpSessionsTest extends TestCase
{
    public function testOneSnmpPerDeviceAndContext(): void
    {
        if (! PhpSnmp::supportsNative()) {
            $this->markTestSkipped('librenms-snmp with SNMP_VALUE_NATIVE required');
        }

        $sessions = new PhpSnmpSessions;
        $backend = new PhpSnmp($sessions);
        $config = SnmpConfig::make(['transport' => 'udp'] + (new SnmpPollingMethod)->defaults(), new SnmpCommunitySecretData(community: 'public'));

        $a = $backend->buildSnmp('127.0.0.1', $config, new SnmpQueryOptions);
        $b = $backend->buildSnmp('127.0.0.1', $config, new SnmpQueryOptions(numericEnums: true));
        $c = $backend->buildSnmp('127.0.0.1', $config, new SnmpQueryOptions(context: 'vlan-2'));
        $d = $backend->buildSnmp('127.0.0.2', $config, new SnmpQueryOptions);

        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
        $this->assertNotSame($a, $d);
        $this->assertSame(3, $sessions->count());
        $this->assertSame(0, $b->valueretrieval & SNMP_VALUE_ENUM_LABELS); // options are set per query

        $sessions->flush();
        $this->assertSame(0, $sessions->count());
    }
}
