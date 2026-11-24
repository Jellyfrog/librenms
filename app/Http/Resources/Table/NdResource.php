<?php

namespace App\Http\Resources\Table;

use App\Facades\PortCache;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Util\IPv6;
use LibreNMS\Util\Mac;

/**
 * IPv6 neighbour, remote device and port are the device that has the address
 *
 * @mixin \App\Models\Ipv6Nd
 */
class NdResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mac = Mac::parse($this->mac_address);
        $ipv6 = IPv6::parse($this->ipv6_address, true);
        $remotePort = PortCache::getByIp($ipv6);

        return [
            'id' => $this->getKey(),
            'interface' => Blade::render('<x-port-link :port="$port" />', ['port' => PortCache::get($this->port_id)]),
            'mac_address' => $mac->readable(),
            'mac_oui' => $mac->vendor(),
            'ipv6_address' => $ipv6->compressed(),
            'remote_device' => $remotePort ? Blade::render('<x-device-link :device="$device" />', ['device' => $remotePort->device]) : '',
            'remote_interface' => $remotePort ? Blade::render('<x-port-link :port="$port" />', ['port' => $remotePort]) : '',
        ];
    }
}
