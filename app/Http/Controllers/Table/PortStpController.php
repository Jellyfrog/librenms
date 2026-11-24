<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\PortStpResource;
use App\Models\Port;
use App\Models\PortStp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PortStpController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Port::class);
        $request->validate([
            'device_id' => 'nullable|integer',
            'vlan' => 'nullable|integer',
        ]);

        $query = PortStp::hasAccess($request->user())
            ->with('port')
            ->when($request->integer('device_id'), fn (Builder $q, int $id) => $q->where('device_id', $id))
            // vlan 1 includes ports without a vlan
            ->when($request->filled('vlan'), fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('vlan', $request->integer('vlan'))
                ->when($request->integer('vlan') == 1, fn (Builder $q) => $q->orWhereNull('vlan'))));

        $request->sortQuery($query, [
            'vlan', 'priority', 'state', 'enable', 'pathCost', 'designatedRoot', 'designatedCost', 'designatedBridge', 'designatedPort', 'forwardTransitions',
            'port_id' => fn (Builder $q, string $direction) => $q->orderBy(Port::select('ifName')->whereColumn('ports.port_id', 'ports_stp.port_id'), $direction),
        ], ['port_id' => 'asc']);

        return PortStpResource::collection($request->paginate($query));
    }
}
