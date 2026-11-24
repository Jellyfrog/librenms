<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Enum\PortSecurityStatus;

/**
 * @mixin \App\Models\PortSecurity
 */
class PortSecurityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'device' => Blade::render('<x-device-link :device="$device"/>', ['device' => $this->device]),
            'port' => $this->port ? Blade::render('<x-port-link :port="$port">{{ $port->getShortLabel() }}</x-port-link>', ['port' => $this->port]) : 'N/A',
            'ifAlias' => $this->port->ifAlias ?? 'N/A',
            'enabled' => $this->port_security_enable === null ? 'N/A' : ($this->port_security_enable ? __('Yes') : __('No')),
            'status' => $this->status ?? 'N/A',
            'status_icon' => PortSecurityStatus::getIconClass($this->status ?? ''),
            'address_count' => $this->address_count ?? 'N/A',
            'max_addresses' => $this->max_addresses ?? 'N/A',
            'violation_action' => $this->violation_action ?? 'N/A',
            'violation_count' => $this->violation_count ?? 'N/A',
            'last_mac_address' => $this->last_mac_address ?? 'N/A',
            'sticky' => $this->sticky_enable === null ? 'N/A' : ($this->sticky_enable ? __('Yes') : __('No')),
        ];
    }
}
