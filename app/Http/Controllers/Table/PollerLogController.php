<?php

namespace App\Http\Controllers\Table;

use App\Facades\LibrenmsConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\PollerLogResource;
use App\Models\Device;
use App\Models\Location;
use App\Models\PollerGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Last poll of each polled device, unpolled=1 limits it to devices that are overdue
 */
class PollerLogController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Device::class);
        $request->validate(['unpolled' => 'nullable|boolean']);

        $query = Device::hasAccess($request->user())
            ->with(['location', 'pollerGroup'])
            ->where('status', 1)
            ->where('ignore', 0)
            ->where('disabled', 0)
            ->when($request->boolean('unpolled'), fn (Builder $q) => $q->where('last_polled', '<=', now()->subSeconds((int) (LibrenmsConfig::get('rrd.step', 300) * 1.2))));

        $request->searchQuery($query, ['hostname', 'sysName', 'display']);
        $request->sortQuery($query, [
            'hostname' => 'hostname',
            'last_polled' => 'last_polled',
            'last_polled_timetaken' => 'last_polled_timetaken',
            'poller_group' => fn (Builder $q, string $direction) => $q->orderBy(PollerGroup::select('group_name')->whereColumn('poller_groups.id', 'devices.poller_group'), $direction),
            'location' => fn (Builder $q, string $direction) => $q->orderBy(Location::select('location')->whereColumn('locations.id', 'devices.location_id'), $direction),
        ], ['last_polled_timetaken' => 'desc']);

        return PollerLogResource::collection($request->paginate($query));
    }
}
