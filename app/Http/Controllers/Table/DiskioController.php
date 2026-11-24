<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\DiskioResource;
use App\Models\UcdDiskio;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DiskioController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', UcdDiskio::class);

        $query = UcdDiskio::query()
            ->hasAccess($request->user())
            ->with('device')
            ->withAggregate('device', 'hostname');
        $request->searchQuery($query, ['diskio_descr', 'device.hostname', 'device.display']);
        $request->sortQuery($query, ['device_hostname', 'diskio_descr'], ['device_hostname' => 'asc', 'diskio_descr' => 'asc']);

        return DiskioResource::collection($request->paginate($query));
    }
}
