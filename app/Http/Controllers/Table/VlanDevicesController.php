<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\VlanDeviceResource;
use App\Models\Device;
use App\Models\Vlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Devices that have a vlan, with the vlan's local settings and the number of ports in it
 */
class VlanDevicesController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vlan::class);
        $request->validate(['vlan' => 'required|integer']);
        $vlanId = $request->integer('vlan');

        $query = Device::query()
            ->distinct()
            ->hasAccess($request->user())
            ->select(['devices.*', 'vlans.vlan_name', 'vlans.vlan_domain', 'vlans.vlan_type', 'vlans.vlan_state'])
            ->withCount(['ports' => fn (Builder $q) => $q->distinct()->where('ifVlan', $vlanId)->orWhereHas('vlans', fn (Builder $q) => $q->where('vlan', $vlanId))])
            ->leftJoin('vlans', fn ($join) => $join->on('devices.device_id', '=', 'vlans.device_id')->where('vlans.vlan_vlan', '=', $vlanId))
            ->where(fn (Builder $q) => $q->where('vlans.vlan_vlan', $vlanId)->orWhereHas('ports', fn (Builder $q) => $q->where('ifVlan', $vlanId)));

        $request->searchQuery($query, ['hostname', 'display', 'sysName']);
        $request->sortQuery($query, [
            'device' => 'devices.display',
            'ports_count' => 'ports_count',
            'domain' => 'vlans.vlan_domain',
            'name' => 'vlans.vlan_name',
            'type' => 'vlans.vlan_type',
            'state' => 'vlans.vlan_state',
        ], ['device' => 'asc']);

        return VlanDeviceResource::collection($request->paginate($query));
    }
}
