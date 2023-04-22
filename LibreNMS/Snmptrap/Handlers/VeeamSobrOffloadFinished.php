<?php

namespace LibreNMS\Snmptrap\Handlers;

use App\Models\Device;
use LibreNMS\Interfaces\SnmptrapHandler;
use LibreNMS\Snmptrap\Trap;

class VeeamSobrOffloadFinished implements SnmptrapHandler
{
    /**
     * Handle snmptrap.
     * Data is pre-parsed and delivered as a Trap.
     */
    public function handle(Device $device, Trap $trap): void
    {
        $name = $trap->getOidData('VEEAM-MIB::repositoryName');
        $result = $trap->getOidData('VEEAM-MIB::repositoryStatus');
        $color = ['Success' => 1, 'Warning' => 4, 'Failed' => 5];

        $trap->log('SNMP Trap: Scale-out offload job ' . $result . ' - ' . $name, $color[$result], 'backup');
    }
}
