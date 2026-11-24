<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Models\Device;
use App\Models\PeeringdbIx;
use App\Models\PeeringdbIxPeer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use LibreNMS\Util\AutonomousSystem;
use LibreNMS\Util\Url;

/**
 * PeeringDB pages: our public ASNs, the exchanges of an ASN and the peers at an exchange
 */
class PeeringController extends Controller
{
    public function asns(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Device::class);

        // public ASNs only: not 64512 - 65535 (private, well known) or 4200000000 and up (private, reserved)
        $query = Device::hasAccess($request->user())
            ->select('bgpLocalAs')
            ->where('disabled', 0)
            ->where('ignore', 0)
            ->where('bgpLocalAs', '>', 0)
            ->where(fn (Builder $q) => $q->where('bgpLocalAs', '<', 64512)->orWhere('bgpLocalAs', '>', 65535))
            ->where('bgpLocalAs', '<', 4200000000)
            ->groupBy('bgpLocalAs');
        $request->searchQuery($query, ['bgpLocalAs']);
        $request->sortQuery($query, ['bgpLocalAs'], ['bgpLocalAs' => 'asc']);

        return JsonResource::collection($request->paginate($query)->through(fn (Device $device) => [
            'id' => $device->bgpLocalAs,
            'bgpLocalAs' => $device->bgpLocalAs,
            'asname' => AutonomousSystem::get($device->bgpLocalAs)->name(),
            'url' => Url::generate(['page' => 'peering', 'section' => 'ix-list', 'bgpLocalAs' => $device->bgpLocalAs]),
        ]));
    }

    public function exchanges(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Device::class);
        $request->validate(['asn' => 'required|integer']);

        $query = PeeringdbIx::where('asn', $request->integer('asn'));
        $request->searchQuery($query, ['name']);
        $request->sortQuery($query, ['exchange' => 'name'], ['exchange' => 'asc']);

        return JsonResource::collection($request->paginate($query)->through(fn (PeeringdbIx $ix) => [
            'id' => $ix->ix_id,
            'exchange' => $ix->name,
            'peers_url' => Url::generate(['page' => 'peering', 'section' => 'ix-peers', 'asn' => $request->integer('asn'), 'ixid' => $ix->ix_id]),
            'pdb_url' => 'https://peeringdb.com/ix/' . $ix->ix_id,
        ]));
    }

    public function peers(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Device::class);
        $request->validate([
            'ixid' => 'required|integer',
            'status' => 'nullable|in:all,connected,unconnected',
        ]);

        // connected peers have a bgp session to their address
        $query = PeeringdbIxPeer::query()
            ->from('pdb_ix_peers as P')
            ->leftJoin('bgpPeers', 'P.remote_ipaddr4', '=', 'bgpPeers.bgpPeerIdentifier')
            ->where('P.ix_id', $request->integer('ixid'))
            ->whereNotNull('P.remote_ipaddr4')
            ->when($request->input('status') === 'connected', fn (Builder $q) => $q->whereColumn('P.remote_ipaddr4', 'bgpPeers.bgpPeerIdentifier'))
            ->when($request->input('status') === 'unconnected', fn (Builder $q) => $q->whereNull('bgpPeers.bgpPeerRemoteAs'))
            ->select(['P.remote_asn', 'P.name', 'P.remote_ipaddr4', 'P.peer_id', DB::raw('max(bgpPeers.bgpPeerIdentifier) as bgpPeerIdentifier')])
            ->groupBy('P.name', 'P.remote_ipaddr4', 'P.peer_id', 'P.remote_asn');

        if ($search = $request->search()) {
            $query->where(fn (Builder $q) => $q->where('P.remote_ipaddr4', 'like', "%$search%")
                ->orWhere('P.remote_asn', 'like', "%$search%")
                ->orWhere('P.name', 'like', "%$search%"));
        }
        $request->sortQuery($query, ['remote_asn' => 'P.remote_asn', 'remote_ipaddr4' => 'P.remote_ipaddr4'], ['remote_asn' => 'asc']);

        return JsonResource::collection($request->paginate($query)->through(fn (PeeringdbIxPeer $peer) => [
            'id' => $peer->peer_id . '-' . $peer->remote_ipaddr4,
            'remote_asn' => $peer->remote_asn,
            'remote_ipaddr4' => $peer->remote_ipaddr4,
            'peer' => $peer->name,
            'connected' => $peer->remote_ipaddr4 === $peer->getAttribute('bgpPeerIdentifier'),
            'pdb_url' => 'https://peeringdb.com/asn/' . $peer->remote_asn,
        ]));
    }
}
