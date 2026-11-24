<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\StorageResource;
use App\Models\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LibreNMS\Util\Number;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoragesController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $query = $this->query($request)->with('device')->withAggregate('device', 'hostname');
        $request->searchQuery($query, ['storage_descr', 'device.hostname', 'device.display']);
        $request->sortQuery($query, ['device_hostname', 'storage_descr', 'storage_used', 'storage_perc'], ['device_hostname' => 'asc', 'storage_descr' => 'asc']);

        return StorageResource::collection($request->paginate($query));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $rows = $this->query($request)->with('device')->lazy()->map(fn (Storage $storage) => [
            $storage->device?->display,
            $storage->storage_descr,
            Number::formatBi($storage->storage_used),
            Number::formatBi($storage->storage_free),
        ]);

        return $this->streamCsv('storage', ['Device Hostname', 'Storage', 'Used', 'Free'], $rows);
    }

    /**
     * @return Builder<Storage>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', Storage::class);
        $request->validate(['status' => 'nullable|string', 'view' => 'nullable|in:detail,graphs']);

        return Storage::query()
            ->hasAccess($request->user())
            // only entries in warning state
            ->when($request->input('status') == 'warning', fn (Builder $q) => $q->where('storage_perc', '>', 0)
                ->whereColumn('storage_perc', '>=', 'storage_perc_warn'));
    }
}
