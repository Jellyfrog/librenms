<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\PortResource;
use App\Models\Port;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use LibreNMS\Util\Number;
use LibreNMS\Util\Rewrite;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortsController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $query = $this->query($request)->with(['device', 'device.location']);

        return PortResource::collection($request->paginate($query));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $rows = $request->exportRows($this->query($request)->with('device'))->map(fn (Port $port) => [
            $port->device_id,
            $port->device?->display,
            $port->ifName ?: $port->ifDescr,
            $port->ifIndex,
            $port->ifOperStatus?->value,
            $port->ifAdminStatus?->value,
            $port->ifDuplex,
            Number::formatSi($port->ifSpeed),
            $port->ifMtu,
            Rewrite::normalizeIfType($port->ifType),
            Number::formatBi($port->ifInOctets_rate * 8) . 'bps',
            Number::formatBi($port->ifOutOctets_rate * 8) . 'bps',
            $port->ifInErrors,
            $port->ifOutErrors,
            $port->poll_period ? Number::formatSi($port->ifInErrors_delta / $port->poll_period, 2, 0, 'EPS') : '',
            $port->poll_period ? Number::formatSi($port->ifOutErrors_delta / $port->poll_period, 2, 0, 'EPS') : '',
            $port->ifAlias,
            $port->device ? ($port->device->uptime - ($port->ifLastChange / 100)) : 'N/A',
            $port->ifConnectorPresent == 'true' ? 'yes' : 'no',
        ]);

        return $this->streamCsv('ports', [
            'Device ID', 'Hostname', 'Port', 'ifIndex', 'Status', 'Admin Status', 'Duplex', 'Speed', 'MTU', 'Type',
            'In Rate (bps)', 'Out Rate (bps)', 'In Errors', 'Out Errors', 'In Error Rate', 'Out Error Rate', 'Description', 'Last Change', 'Connector Present',
        ], $rows);
    }

    /**
     * @return Builder<Port>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', Port::class);
        $request->validate(Port::filterValidationRules());

        $query = Port::hasAccess($request->user())
            ->select('ports.*')
            ->leftJoin('devices', 'ports.device_id', 'devices.device_id')
            ->applyFilters($request->filters())
            // deleted ports only when the filter asks for them
            ->unless(isset($request->filters()['deleted']), fn (Builder $q) => $q->where('ports.deleted', 0));

        return $request->sortQuery($query, [
            'hostname' => 'devices.hostname',
            'ifIndex', 'ifDescr', 'ifConnectorPresent', 'ifInErrors', 'ifOutErrors', 'ifInErrors_delta', 'ifOutErrors_delta',
            'ifInOctets_rate', 'ifOutOctets_rate', 'ifInUcastPkts_rate', 'ifOutUcastPkts_rate', 'ifType', 'ifAlias', 'ifMtu', 'ifSpeed', 'ifDuplex',
            'errors' => DB::raw('ifInErrors_rate + ifOutErrors_rate'),
            'secondsIfLastChange' => DB::raw('`devices`.`uptime` - `ports`.`ifLastChange` / 100'),
        ], ['ifDescr' => 'asc']);
    }
}
