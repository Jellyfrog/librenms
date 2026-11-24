<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\VlanPortResource;
use App\Models\Device;
use App\Models\Port;
use App\Models\Vlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Ports in a vlan: untagged by ifVlan or tagged/untagged in ports_vlans
 */
class VlanPortsController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Vlan::class);
        $request->validate(['vlan' => 'required|integer']);
        $vlanId = $request->integer('vlan');

        $query = Port::hasAccess($request->user())
            ->with(['device', 'device.location'])
            ->leftJoin('ports_vlans', 'ports.port_id', 'ports_vlans.port_id')
            ->where(fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('ifVlan', $vlanId)->whereNull('ports_vlans.vlan'))
                ->orWhere('ports_vlans.vlan', $vlanId))
            ->select([
                'ports.port_id', 'ports.device_id', 'ports.ifName', 'ports.ifIndex', 'ports.ifDescr', 'ports.ifAlias', 'ports.ifVlan',
                'ports.ifAdminStatus', 'ports.ifOperStatus', 'ports_vlans.state', 'ports_vlans.cost',
                DB::raw('CASE WHEN ports.ifVlan = ' . $vlanId . ' or ports_vlans.untagged <> 0 THEN "yes" ELSE "no" END as untagged'),
            ]);

        $request->searchQuery($query, ['ifName', 'ifDescr', 'ifAlias', 'device.hostname', 'device.display', 'device.sysName']);
        $request->sortQuery($query, [
            'device' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('display')->whereColumn('devices.device_id', 'ports.device_id'), $direction),
            'port' => 'ports.ifName',
            'untagged' => 'untagged',
            'state' => 'ports_vlans.state',
            'cost' => 'ports_vlans.cost',
        ], ['device' => 'asc', 'port' => 'asc']);

        return VlanPortResource::collection($request->paginate($query));
    }
}
