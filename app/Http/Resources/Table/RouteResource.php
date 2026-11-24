<?php

namespace App\Http\Resources\Table;

use App\Models\Device;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Util\IP;

/**
 * Routing table entry, the next hop links to the device that has the address
 *
 * @property Route $resource
 */
class RouteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;
        $item = [
            'id' => $model->route_id,
            'updated_at' => $model->updated_at ? $model->updated_at->diffForHumans() : $model->updated_at,
            'created_at' => $model->created_at ? $model->created_at->toDateTimeString() : $model->created_at,
            'inetCidrRouteIfIndex' => $model->inetCidrRouteIfIndex == 0 ? 'Undefined' : $model->inetCidrRouteIfIndex,
            'inetCidrRouteMetric1' => $model->inetCidrRouteMetric1,
            'inetCidrRoutePfxLen' => $model->inetCidrRoutePfxLen,
            'inetCidrRouteDestType' => $model->inetCidrRouteDestType,
        ];

        try {
            $obj_inetCidrRouteDest = IP::parse($model->inetCidrRouteDest);
            $item['inetCidrRouteDest'] = $obj_inetCidrRouteDest->compressed();
        } catch (\Exception) {
            $item['inetCidrRouteDest'] = $model->inetCidrRouteDest;
        }

        $item['inetCidrRouteIfIndex'] = $model->inetCidrRouteIfIndex == 0 ? 'Undefined' : 'IfIndex ' . $model->inetCidrRouteIfIndex;
        if ($port = $model->port) {
            $item['inetCidrRouteIfIndex'] = Blade::render('<x-port-link :port="$port">{{ $port->getShortLabel() }}</x-port-link>', ['port' => $port]);
        }

        try {
            $obj_inetCidrRouteNextHop = IP::parse($model->inetCidrRouteNextHop);
            $item['inetCidrRouteNextHop'] = $obj_inetCidrRouteNextHop->compressed();
        } catch (\Exception) {
            $item['inetCidrRouteNextHop'] = $model->inetCidrRouteNextHop;
        }
        $device = Device::findByIp($model->inetCidrRouteNextHop);
        if ($device) {
            if ($device->device_id == $model->device_id || in_array($model->inetCidrRouteNextHop, ['127.0.0.1', '::1'])) {
                $item['inetCidrRouteNextHop'] = Blade::render('<x-device-link :device="$device">localhost</x-device-link>', ['device' => $device]);
            } else {
                $item['inetCidrRouteNextHop'] = $item['inetCidrRouteNextHop'] . '<br>(' . rtrim(Blade::render('<x-device-link :device="$device"/>', ['device' => $device])) . ')';
            }
        }

        $item['inetCidrRouteProto'] = $model->inetCidrRouteProto;
        if ($model->inetCidrRouteProto && isset($model::$translateProto[$model->inetCidrRouteProto])) {
            $item['inetCidrRouteProto'] = $model::$translateProto[$model->inetCidrRouteProto];
        }

        $item['inetCidrRouteType'] = $model->inetCidrRouteType;
        if ($model->inetCidrRouteType && isset($model::$translateType[$model->inetCidrRouteType])) {
            $item['inetCidrRouteType'] = $model::$translateType[$model->inetCidrRouteType];
        }

        $item['context_name'] = '[global]';
        if ($model->context_name != '') {
            $item['context_name'] = '<a href="' . route('routing.vrf', ['vrf' => $model->context_name]) . '">' . htmlspecialchars((string) $model->context_name) . '</a>';
        }

        return $item;
    }
}
