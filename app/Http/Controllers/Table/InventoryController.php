<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\InventoryResource;
use App\Models\Device;
use App\Models\EntPhysical;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        return InventoryResource::collection($request->paginate($this->query($request)->with('device')));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $rows = $request->exportRows($this->query($request)->with('device'))->map(fn (EntPhysical $item) => [
            $item->device?->display,
            $item->entPhysicalDescr,
            $item->entPhysicalName,
            $item->entPhysicalModelName,
            $item->entPhysicalSerialNum,
        ]);

        return $this->streamCsv('inventory', ['Device', 'Description', 'Name', 'Model', 'Serial Number'], $rows);
    }

    /**
     * @return Builder<EntPhysical>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', EntPhysical::class);
        $request->validate(EntPhysical::filterValidationRules());

        $query = EntPhysical::hasAccess($request->user())
            ->select(['entPhysical_id', 'device_id', 'entPhysicalDescr', 'entPhysicalName', 'entPhysicalModelName', 'entPhysicalSerialNum'])
            ->applyFilters($request->filters());
        $request->searchQuery($query, ['entPhysicalDescr', 'entPhysicalName', 'entPhysicalModelName', 'entPhysicalSerialNum']);

        return $request->sortQuery($query, [
            'device' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('display')->whereColumn('devices.device_id', 'entPhysical.device_id'), $direction),
            'name' => 'entPhysicalName',
            'descr' => 'entPhysicalDescr',
            'model' => 'entPhysicalModelName',
            'serial' => 'entPhysicalSerialNum',
        ], ['device' => 'asc']);
    }
}
