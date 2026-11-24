<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Url;

/**
 * Port row of the port group membership table, the request's membership tab decides the member flag
 *
 * @mixin \App\Models\Port
 */
class PortGroupPortResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $membership = $request->input('membership', 'members');

        return [
            'port_id' => $this->port_id,
            'label' => $this->getLabel(),
            'ifDescr' => $this->ifDescr,
            'ifAlias' => $this->ifAlias,
            'ifSpeed' => $this->ifSpeed,
            'url' => Url::portUrl($this->resource),
            'link_class' => Url::portLinkDisplayClass($this->resource),
            'ifAdminStatus' => $this->ifAdminStatus?->value,
            'ifOperStatus' => $this->ifOperStatus?->value,
            'deleted' => (bool) $this->deleted,
            'device_id' => $this->device_id,
            'device' => $this->device?->display,
            'device_url' => $this->device ? Url::deviceUrl($this->device) : null,
            'member' => $membership === 'all' ? (bool) $this->getAttribute('member') : $membership === 'members',
        ];
    }
}
