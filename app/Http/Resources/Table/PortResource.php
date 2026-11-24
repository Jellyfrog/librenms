<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Enum\IfOperStatus;
use LibreNMS\Util\Number;
use LibreNMS\Util\Rewrite;
use LibreNMS\Util\Url;

/**
 * Port row of the ports list, rates are raw numbers the page formats
 *
 * @mixin \App\Models\Port
 */
class PortResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'port_id' => $this->port_id,
            'status' => match ($this->ifOperStatus) {
                IfOperStatus::Up => 'label-success',
                IfOperStatus::Down, IfOperStatus::LowerLayerDown => $this->ifAdminStatus === IfOperStatus::Up ? 'label-danger' : 'label-default',
                IfOperStatus::Testing => 'label-info',
                default => 'label-default',
            },
            'device' => Url::modernDeviceLink($this->device),
            'port' => Blade::render('<x-port-link :port="$port"/>', ['port' => $this->resource]),
            'secondsIfLastChange' => $this->device ? (int) ceil($this->device->uptime - ($this->ifLastChange / 100)) : null,
            'ifConnectorPresent' => $this->ifConnectorPresent == 'true' ? __('Yes') : __('No'),
            'ifSpeed' => $this->ifSpeed,
            'ifDuplex' => strtolower(trim((string) $this->ifDuplex)),
            'ifMtu' => $this->ifMtu,
            'ifInOctets_rate' => $this->ifInOctets_rate * 8,
            'ifOutOctets_rate' => $this->ifOutOctets_rate * 8,
            'ifInUcastPkts_rate' => $this->ifInUcastPkts_rate,
            'ifOutUcastPkts_rate' => $this->ifOutUcastPkts_rate,
            'ifInErrors' => $this->ifInErrors,
            'ifOutErrors' => $this->ifOutErrors,
            'ifInErrors_delta' => $this->poll_period ? Number::formatSi($this->ifInErrors_delta / $this->poll_period, 2, 0, 'EPS') : '',
            'ifOutErrors_delta' => $this->poll_period ? Number::formatSi($this->ifOutErrors_delta / $this->poll_period, 2, 0, 'EPS') : '',
            'ifType' => Rewrite::normalizeIfType($this->ifType),
            'ifAlias' => $this->ifAlias,
            'actions' => view('port.actions', ['port' => $this->resource])->render(),
        ];
    }
}
