<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Url;

/**
 * Port in a vlan, untagged, state and cost are joined from ports_vlans
 *
 * @mixin \App\Models\Port
 */
class VlanPortResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->port_id,
            'device' => Url::deviceLink($this->device),
            'port' => Url::portLink($this->resource, $this->getFullLabel()),
            'untagged' => $this->getAttribute('untagged'),
            'state' => $this->getAttribute('state'),
            'cost' => $this->getAttribute('cost'),
        ];
    }
}
