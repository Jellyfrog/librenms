<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\ArpResource;
use App\Models\Ipv4Mac;
use App\Models\Port;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArpController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Port::class);
        $request->validate([
            'device_id' => 'nullable|integer',
            'port_id' => 'nullable|integer',
        ]);

        $query = Ipv4Mac::hasAccess($request->user())
            ->with(['port', 'device', 'remote_ports_maybe', 'remote_ports_maybe.device'])
            ->when($request->integer('device_id'), fn (Builder $q, int $id) => $q->where('device_id', $id))
            ->when($request->integer('port_id'), fn (Builder $q, int $id) => $q->where('port_id', $id));

        // a MAC address matches with or without separators
        if ($search = $request->search()) {
            $mac = str_replace([':', ' ', '-', '.', '0x'], '', $search);
            $query->where(fn (Builder $q) => $q->where('ipv4_address', 'like', "%$search%")->orWhere('mac_address', 'like', "%$mac%"));
        }

        $request->sortQuery($query, [
            'interface' => fn (Builder $q, string $direction) => $q->orderBy(Port::select('ifDescr')->whereColumn('ports.port_id', 'ipv4_mac.port_id'), $direction),
            'mac_address', 'ipv4_address',
        ], ['ipv4_address' => 'asc']);

        return ArpResource::collection($request->paginate($query));
    }
}
