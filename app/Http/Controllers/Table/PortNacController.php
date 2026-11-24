<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\NacResource;
use App\Models\Device;
use App\Models\Port;
use App\Models\PortsNac;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PortNacController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Port::class);
        $request->validate([
            'device_id' => 'nullable|integer',
            'port_id' => 'nullable|integer',
            'historical' => 'nullable|boolean',
            'searchby' => 'nullable|in:mac,ip,description,vendor',
        ]);

        $query = PortsNac::hasAccess($request->user())
            ->with(['port', 'device'])
            ->when($request->integer('device_id'), fn (Builder $q, int $id) => $q->where('ports_nac.device_id', $id))
            ->when($request->integer('port_id'), fn (Builder $q, int $id) => $q->where('ports_nac.port_id', $id))
            ->unless($request->boolean('historical'), fn (Builder $q) => $q->where('historical', 0));

        $this->search($request, $query);
        $request->sortQuery($query, [
            'device_id' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('hostname')->whereColumn('devices.device_id', 'ports_nac.device_id'), $direction),
            'port_id' => fn (Builder $q, string $direction) => $q->orderBy(Port::select('ifDescr')->whereColumn('ports.port_id', 'ports_nac.port_id'), $direction),
            'mac_address', 'ip_address', 'vlan', 'domain', 'host_mode', 'username', 'authz_by', 'timeout', 'time_elapsed', 'time_left',
            'authc_status', 'authz_status', 'method', 'created_at', 'updated_at',
        ], ['device_id' => 'asc']);

        return NacResource::collection($request->paginate($query));
    }

    /**
     * The search box matches vendors, port descriptions, VLANs, MAC and IP addresses and usernames, searchby limits it to one
     */
    private function search(DataTableRequest $request, Builder $query): Builder
    {
        if ($search = $request->search()) {
            $mac_search = '%' . str_replace([':', ' ', '-', '.', '0x'], '', $search) . '%';

            switch ($request->input('searchby') ?? '') {
                case 'mac':
                    return $query->where('ports_nac.mac_address', 'like', $mac_search);
                case 'ip':
                    return $query->where('ports_nac.ip_address', 'like', "%$search%");
                case 'description':
                    return $query->whereIntegerInRaw('ports_nac.port_id', $this->findPorts($search));
                case 'vendor':
                    $vendor_ouis = $this->ouisFromVendor($search);

                    return $this->queryByOui($vendor_ouis, $query);
                default:
                    return $query->where(function ($query) use ($search, $mac_search): void {
                        $vendor_ouis = $this->ouisFromVendor($search);
                        $this->queryByOui($vendor_ouis, $query)
                            ->orWhereIntegerInRaw('ports_nac.port_id', $this->findPorts($search))
                            ->orWhere('ports_nac.vlan', 'like', '%' . $search . '%')
                            ->orWhere('ports_nac.mac_address', 'like', $mac_search)
                            ->orWhere('ports_nac.username', 'like', '%' . $search . '%')
                            ->orWhere('ports_nac.ip_address', 'like', '%' . $search . '%');
                    });
            }
        }

        return $query;
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
     * filter $query from vendor OUIs
     */
    private function queryByOui(array $vendor_ouis, Builder $query): Builder
    {
        $query->where(function (Builder $query) use ($vendor_ouis): void {
            foreach ($vendor_ouis as $oui) {
                $query->orWhere('ports_nac.mac_address', 'LIKE', "$oui%");
            }
        });

        return $query; // Return the query builder instance
    }
}
