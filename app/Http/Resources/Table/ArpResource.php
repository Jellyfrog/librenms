<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Mac;
use LibreNMS\Util\Rewrite;
use LibreNMS\Util\Url;

/**
 * ARP table entry, remote device and port are the device that has the MAC address, Localhost for our own port
 *
 * @mixin \App\Models\Ipv4Mac
 */
class ArpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $remotePort = $this->remote_ports_maybe?->first();
        $local = $remotePort ? $remotePort->port_id == $this->port_id : $this->mac_address == $this->port?->ifPhysAddress;
        $mac = Mac::parse($this->mac_address);
        $errors = $this->port && ($this->port->ifInErrors_delta > 0 || $this->port->ifOutErrors_delta > 0);

        return [
            'id' => $this->port_id . '-' . $this->mac_address . '-' . $this->ipv4_address,
            'mac_address' => $mac->readable(),
            'mac_oui' => $mac->vendor(),
            'ipv4_address' => $this->ipv4_address,
            'hostname' => Url::deviceLink($this->device),
            'interface' => Url::portLink($this->port, Rewrite::shortenIfName($this->port?->getLabel()))
                . ($errors ? ' ' . Url::portLink($this->port, "<i class='fa fa-flag fa-lg' style='color:red' aria-hidden='true'></i>", 'port_errors') : ''),
            'remote_device' => $local ? __('Localhost') : ($remotePort ? Url::deviceLink($remotePort->device) : ''),
            'remote_interface' => $local ? __('Local port') : ($remotePort ? Url::portLink($remotePort) : ''),
        ];
    }
}
