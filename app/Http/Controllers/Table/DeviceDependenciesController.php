<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\DeviceDependencyResource;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeviceDependenciesController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('device.update');

        $query = Device::hasAccess($request->user())->with('parents');
        $request->searchQuery($query, ['hostname', 'sysName', 'display', 'parents.hostname', 'parents.sysName', 'parents.display']);
        $request->sortQuery($query, [
            'device_id' => 'device_id',
            'hostname' => 'hostname',
            'parents' => fn (Builder $q, string $direction) => $q->withMin('parents', 'hostname')->orderBy('parents_min_hostname', $direction)->orderBy('hostname', $direction),
        ], ['hostname' => 'asc']);

        return DeviceDependencyResource::collection($request->paginate($query));
    }
}
