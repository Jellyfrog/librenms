<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Url;

/**
 * Device in a vlan, the vlan_* fields are joined from vlans
 *
 * @mixin \App\Models\Device
 */
class VlanDeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->device_id,
            'device' => Url::deviceLink($this->resource),
            'ports_count' => $this->ports_count,
            'domain' => $this->getAttribute('vlan_domain'),
            'name' => $this->getAttribute('vlan_name'),
            'type' => $this->getAttribute('vlan_type'),
            'state' => (bool) $this->getAttribute('vlan_state'),
        ];
    }
}
