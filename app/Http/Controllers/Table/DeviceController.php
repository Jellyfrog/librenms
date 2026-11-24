<?php

namespace App\Http\Controllers\Table;

use App\Facades\LibrenmsConfig;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\DeviceResource;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use LibreNMS\Util\Rewrite;
use LibreNMS\Util\Time;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeviceController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $query = $this->query($request)
            ->with(['location', 'attribs' => fn ($q) => $q->where('attrib_type', 'override_device_http_port')])
            ->withCount(['ports', 'sensors', 'wirelessSensors']);

        return DeviceResource::collection($request->paginate($query));
    }

    /**
     * Every device matching the filters as CSV, in the table's sort order
     */
    public function export(DataTableRequest $request): StreamedResponse
    {
        $rows = $this->query($request)->with('location')->lazy()->map(fn (Device $device) => [
            $device->device_id,
            $device->display,
            $device->ip,
            Rewrite::ciscoHardware($device),
            LibrenmsConfig::getOsSetting($device->os, 'text', $device->os),
            $device->version,
            $device->features,
            $device->location?->location,
            $device->status ? Time::formatInterval($device->uptime, true) : 'Down',
            match (true) {
                (bool) $device->disabled => 'Disabled',
                (bool) $device->ignore => 'Ignored',
                default => $device->status ? 'Up' : 'Down',
            },
            $device->type,
            $device->last_polled,
        ]);

        return $this->streamCsv('devices', [
            'Device ID', 'Hostname', 'IP Address', 'Hardware', 'OS', 'Version', 'Features', 'Location', 'Uptime', 'Status', 'Type', 'Last Polled',
        ], $rows);
    }

    /**
     * @return Builder<Device>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', Device::class);
        $request->validate([
            'format' => 'nullable|in:list_basic,list_detail',
            ...Device::filterValidationRules(),
        ]);

        $query = Device::hasAccess($request->user())
            ->select('devices.*')
            ->applyFilters($request->filters());

        return $request->sortQuery($query, [
            'status' => 'status',
            'icon' => 'icon',
            'hostname' => 'hostname',
            'display' => 'display',
            'hardware' => 'hardware',
            'os' => fn (Builder $q, string $direction) => $q->orderBy('os', $direction)->orderBy('version', $direction)->orderBy('display', $direction),
            'uptime' => DB::raw('IF(`status` = 1, `uptime`, `last_polled` - NOW())'),
            'location' => fn (Builder $q, string $direction) => $q->leftJoin('locations', 'locations.id', 'devices.location_id')->orderBy('locations.location', $direction),
            'device_id' => 'device_id',
        ], ['display' => 'asc']);
    }
}
