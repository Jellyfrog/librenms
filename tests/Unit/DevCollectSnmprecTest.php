<?php

namespace LibreNMS\Tests\Unit;

use App\Console\Commands\DevCollectSnmprec;
use LibreNMS\Data\Source\Snmp\SnmpResponse;
use LibreNMS\Tests\TestCase;

class DevCollectSnmprecTest extends TestCase
{
    public function testTypedValuesToSnmprec(): void
    {
        if (! defined('SNMP_VALUE_NATIVE')) {
            $this->markTestSkipped('librenms-snmp with SNMP_VALUE_NATIVE required');
        }

        $response = SnmpResponse::fromNative([
            '.1.3.6.1.2.1.1.1.0' => (object) ['type' => SNMP_OCTET_STR, 'value' => 'Linux box'],
            '.1.3.6.1.2.1.1.2.0' => (object) ['type' => SNMP_OBJECT_ID, 'value' => '.1.3.6.1.4.1.8072'],
            '.1.3.6.1.2.1.1.3.0' => (object) ['type' => SNMP_TIMETICKS, 'value' => 5],
            '.1.3.6.1.2.1.2.2.1.6.1' => (object) ['type' => SNMP_OCTET_STR, 'value' => "\x00\x1a\x2b"],
            '.1.3.6.1.2.1.4.20.1.1.10.0.0.1' => (object) ['type' => SNMP_IPADDRESS, 'value' => '10.0.0.1'],
            '.1.3.6.1.2.1.31.1.1.1.6.1' => (object) ['type' => SNMP_COUNTER64, 'value' => '18446744073709551615'],
            '.1.3.6.1.2.1.31.1.1.1.18.1' => (object) ['type' => SNMP_NULL, 'value' => null],
        ]);

        $method = new \ReflectionMethod(DevCollectSnmprec::class, 'convertTypedToSnmprec');

        $this->assertSame([
            '1.3.6.1.2.1.1.1.0|4|Linux box',
            '1.3.6.1.2.1.1.2.0|6|1.3.6.1.4.1.8072',
            '1.3.6.1.2.1.1.3.0|67|5',
            '1.3.6.1.2.1.2.2.1.6.1|4x|001a2b',
            '1.3.6.1.2.1.4.20.1.1.10.0.0.1|64|10.0.0.1',
            '1.3.6.1.2.1.31.1.1.1.6.1|70|18446744073709551615',
            '1.3.6.1.2.1.31.1.1.1.18.1|5|',
        ], $method->invoke(app(DevCollectSnmprec::class), $response));
    }
}
