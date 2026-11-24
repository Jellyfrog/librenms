<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\PortSecurityResource;
use App\Models\Device;
use App\Models\Port;
use App\Models\PortSecurity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortSecurityController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        return PortSecurityResource::collection($request->paginate($this->query($request)));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $withDevice = ! $request->integer('device_id');
        $bool = fn (?bool $value) => $value === null ? '' : ($value ? 'true' : 'false');

        $rows = $request->exportRows($this->query($request))->map(fn (PortSecurity $item) => [
            ...($withDevice ? [$item->device_id, $item->device->display ?? ''] : []),
            $item->port?->getShortLabel() ?? '',
            $item->port->ifName ?? '',
            $item->port->ifDescr ?? '',
            $item->port->ifAlias ?? '',
            $bool($item->port_security_enable),
            $item->status ?? '',
            $item->address_count ?? '',
            $item->max_addresses ?? '',
            $item->violation_action ?? '',
            $item->violation_count ?? '',
            $item->last_mac_address ?? '',
            $bool($item->sticky_enable),
        ]);

        return $this->streamCsv('port_security', [
            ...($withDevice ? ['device_id', 'hostname'] : []),
            'port', 'ifName', 'ifDescr', 'ifAlias', 'enabled', 'status', 'current_macs', 'max_macs', 'violation_action', 'violations', 'last_mac', 'sticky',
        ], $rows);
    }

    /**
     * @return Builder<PortSecurity>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', Port::class);
        $request->validate([
            'device_id' => 'nullable|integer',
            ...PortSecurity::filterValidationRules(),
        ]);

        $query = PortSecurity::query()
            ->hasAccess($request->user())
            ->with(['device', 'port'])
            ->select('port_security.*')
            ->leftJoin('ports', 'port_security.port_id', '=', 'ports.port_id')
            ->when($request->integer('device_id'), fn (Builder $q, int $id) => $q->where('port_security.device_id', $id))
            ->applyFilters($request->filters());

        return $request->sortQuery($query, [
            'device' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('hostname')->whereColumn('devices.device_id', 'port_security.device_id'), $direction),
            'port' => 'ports.ifIndex',
            'ifAlias' => 'ports.ifAlias',
            'status' => 'port_security.status',
            'address_count', 'max_addresses', 'violation_action', 'violation_count', 'last_mac_address',
        ], ['device' => 'asc', 'port' => 'asc']);
    }
}
