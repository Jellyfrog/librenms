<?php

namespace App\Listeners;

use App\Events\DeviceDiscovered;
use App\Events\DevicePolled;
use LibreNMS\Data\Source\Snmp\PhpSnmpSessions;

class FlushPhpSnmpSessions
{
    public function __construct(private readonly PhpSnmpSessions $sessions)
    {
    }

    public function handle(DevicePolled|DeviceDiscovered $event): void
    {
        $this->sessions->flush();
    }
}
