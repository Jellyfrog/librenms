<?php

namespace App\Http\Resources\Table;

use App\Facades\DeviceCache;
use App\Models\PortStp;
use App\Models\Stp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Util\Mac;

/**
 * STP port, the designated root and bridge link to the device with that bridge address
 *
 * @property PortStp $resource
 */
class PortStpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;
        $drMac = Mac::parse($model->designatedRoot);
        $dbMac = Mac::parse($model->designatedBridge);

        $dr = DeviceCache::get(Stp::where('bridgeAddress', $model->designatedRoot)->whereNot('bridgeAddress', '000000000000')->value('device_id'));
        $db = DeviceCache::get(Stp::where('bridgeAddress', $model->designatedBridge)->whereNot('bridgeAddress', '')->value('device_id'));

        return [
            'id' => $model->port_stp_id,
            'port_id' => Blade::render('<x-port-link :port="$port">{{ $port->getShortLabel() }}</x-port-link><br /> {{ $port->getDescription() }}', ['port' => $model->port]),
            'vlan' => $model->vlan ?: 1,
            'priority' => $model->priority,
            'state' => $model->state,
            'enable' => $model->enable,
            'pathCost' => $model->pathCost,
            'designatedRoot' => $drMac->readable(),
            'designatedRoot_vendor' => $drMac->vendor(),
            'designatedRoot_device' => $dr ? Blade::render('<x-device-link :device="$device"/>', ['device' => $dr]) : '',
            'designatedCost' => $model->designatedCost,
            'designatedBridge' => $dbMac->readable(),
            'designatedBridge_vendor' => $dbMac->vendor(),
            'designatedBridge_device' => $db ? Blade::render('<x-device-link :device="$device"/>', ['device' => $db]) : '',
            'designatedPort' => $model->designatedPort,
            'forwardTransitions' => $model->forwardTransitions,
        ];
    }
}
