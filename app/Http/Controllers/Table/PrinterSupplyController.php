<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\PrinterSupplyResource;
use App\Models\Device;
use App\Models\PrinterSupply;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrinterSupplyController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Device::class);
        $request->validate(['view' => 'nullable|in:detail,graphs']);

        $query = PrinterSupply::query()
            ->hasAccess($request->user())
            ->with('device')
            ->withAggregate('device', 'hostname');
        $request->searchQuery($query, ['supply_descr', 'supply_type', 'device.hostname', 'device.display']);
        $request->sortQuery($query, ['device_hostname', 'supply_descr', 'supply_type', 'supply_current'], ['device_hostname' => 'asc', 'supply_descr' => 'asc']);

        return PrinterSupplyResource::collection($request->paginate($query));
    }
}
