<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\FdbResource;
use App\Models\Device;
use App\Models\Ipv4Mac;
use App\Models\Port;
use App\Models\PortsFdb;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FdbTablesController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Port::class);
        $request->validate([
            'port_id' => 'nullable|integer',
            'device_id' => 'nullable|integer',
            'searchby' => 'nullable|in:mac,vlan,dnsname,ip,description,first_seen,last_seen,vendor',
        ]);

        $query = PortsFdb::hasAccess($request->user())
            ->with(['device', 'port' => fn ($q) => $q->withCount('fdbEntries'), 'ipv4Addresses'])
            ->select('ports_fdb.*')
            ->when($request->integer('device_id'), fn (Builder $q, int $id) => $q->where('ports_fdb.device_id', $id))
            ->when($request->integer('port_id'), fn (Builder $q, int $id) => $q->where('ports_fdb.port_id', $id));

        $this->search($request, $query);
        $request->sortQuery($query, [
            'mac_address' => 'ports_fdb.mac_address',
            'vlan' => 'ports_fdb.vlan',
            'device' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('hostname')->whereColumn('devices.device_id', 'ports_fdb.device_id'), $direction),
            'interface' => fn (Builder $q, string $direction) => $q->orderBy(Port::select('ifDescr')->whereColumn('ports.port_id', 'ports_fdb.port_id'), $direction),
            'description' => fn (Builder $q, string $direction) => $q->orderBy(Port::select('ifAlias')->whereColumn('ports.port_id', 'ports_fdb.port_id'), $direction),
            'last_seen' => 'ports_fdb.updated_at',
            'first_seen' => 'ports_fdb.created_at',
        ], ['mac_address' => 'asc']);

        return FdbResource::collection($request->paginate($query));
    }

    /**
     * The search box matches MAC addresses, port descriptions, VLANs and IP addresses, searchby limits it to one
     */
    private function search(DataTableRequest $request, Builder $query): Builder
    {
        if ($search = $request->search()) {
            $mac_search = '%' . str_replace([':', ' ', '-', '.', '0x'], '', $search) . '%';
            $vlans = ctype_digit($search) ? [$search] : [];
            switch ($request->input('searchby') ?? '') {
                case 'mac':
                    return $query->where('ports_fdb.mac_address', 'like', $mac_search);
                case 'vlan':
                    return $query->whereIn('ports_fdb.vlan', $vlans);
                case 'dnsname':
                    $search = gethostbyname($search);
                    // no break
                case 'ip':
                    return $query->whereIn('ports_fdb.mac_address', $this->findMacs($search));
                case 'description':
                    return $query->whereIntegerInRaw('ports_fdb.port_id', $this->findPorts($search));
                case 'vendor':
                    $vendor_ouis = $this->ouisFromVendor($search);

                    return $this->findPortsByOui($vendor_ouis, $query);
                default:
                    return $query->where(function ($query) use ($search, $mac_search, $vlans): void {
                        $query->where('ports_fdb.mac_address', 'like', $mac_search)
                            ->orWhereIntegerInRaw('ports_fdb.port_id', $this->findPorts($search))
                            ->orWhereIn('ports_fdb.vlan', $vlans)
                            ->orWhereIn('ports_fdb.mac_address', $this->findMacs($search));
                    });
            }
        }

        return $query;
    }

    /**
     * @param  string  $ip
     * @return Collection<int, string>
     */
    private function findMacs(string $ip): Collection
    {
        $port_id = request()->input('port_id');
        $device_id = request()->input('device_id');

        return Ipv4Mac::where('ipv4_address', 'like', "%$ip%")
            ->when($device_id, fn ($query) => $query->where('device_id', $device_id))
            ->when($port_id, fn ($query) => $query->where('port_id', $port_id))
            ->pluck('mac_address');
    }

    /**
     * @param  string  $ifAlias
     * @return Collection<int, int>
     */
    private function findPorts(string $ifAlias): Collection
    {
        $port_id = request()->input('port_id');
        $device_id = request()->input('device_id');

        return Port::where('ifAlias', 'like', "%$ifAlias%")
            ->when($device_id, fn ($query) => $query->where('device_id', $device_id))
            ->when($port_id, fn ($query) => $query->where('port_id', $port_id))
            ->pluck('port_id');
    }

    /**
     * Get the OUI list for a specific vendor
     *
     * @param  string  $vendor
     * @return array
     */
    private function ouisFromVendor(string $vendor): array
    {
        return DB::table('vendor_ouis')
            ->where('vendor', 'LIKE', '%' . $vendor . '%')
            ->pluck('oui')
            ->toArray();
    }

    /**
     * Get all port ids from vendor OUIs
     */
    private function findPortsByOui(array $vendor_ouis, Builder $query): Builder
    {
        $query->where(function (Builder $query) use ($vendor_ouis): void {
            foreach ($vendor_ouis as $oui) {
                $query->orWhere('ports_fdb.mac_address', 'LIKE', "$oui%");
            }
        });

        return $query; // Return the query builder instance
    }
}
