<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\RouteResource;
use App\Models\Port;
use App\Models\Route;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoutesTablesController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Route::class);
        $request->validate([
            'device_id' => 'required|integer',
            'historical' => 'nullable|boolean',
            'protocol' => 'nullable|in:ipv4,ipv6',
        ]);

        $deviceId = $request->integer('device_id');
        $query = Route::hasAccess($request->user())
            ->with('port')
            ->where('route.device_id', $deviceId)
            ->when($request->input('protocol'), fn (Builder $q, string $protocol) => $q->where('route.inetCidrRouteDestType', $protocol))
            // only the routes found by the last discovery, unless historical routes are asked for
            ->unless($request->boolean('historical'), fn (Builder $q) => $q->where('route.updated_at', Route::hasAccess($request->user())->where('route.device_id', $deviceId)->max('updated_at')));

        $request->searchQuery($query, ['inetCidrRouteNextHop', 'inetCidrRouteDest']);
        $request->sortQuery($query, [
            'context_name', 'inetCidrRouteDestType', 'inetCidrRouteDest', 'inetCidrRoutePfxLen', 'inetCidrRouteNextHop',
            'inetCidrRouteMetric1', 'inetCidrRouteType', 'inetCidrRouteProto', 'created_at', 'updated_at',
            'inetCidrRouteIfIndex' => fn (Builder $q, string $direction) => $q->orderBy(Port::select('ifDescr')->whereColumn('ports.port_id', 'route.port_id'), $direction)->orderBy('inetCidrRouteIfIndex', $direction),
        ], ['inetCidrRouteDest' => 'asc']);

        return RouteResource::collection($request->paginate($query));
    }
}
