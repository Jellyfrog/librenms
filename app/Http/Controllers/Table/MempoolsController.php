<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\MempoolResource;
use App\Models\Device;
use App\Models\Mempool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LibreNMS\Util\Number;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MempoolsController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Mempool::class);
        $request->validate(['status' => 'nullable|string', 'view' => 'nullable|in:detail,graphs']);

        // the graphs view shows one graph row per device
        if ($request->input('view') == 'graphs') {
            $query = Device::hasAccess($request->user())
                ->whereHas('mempools', fn (Builder $q) => $this->warning($request, $q));
            $request->searchQuery($query, ['hostname', 'display']);
            $request->sortQuery($query, ['hostname' => 'hostname'], ['hostname' => 'asc']);

            return MempoolResource::collection($request->paginate($query));
        }

        $query = $this->query($request)->with('device')->withAggregate('device', 'hostname');
        $request->searchQuery($query, ['mempool_descr', 'device.hostname', 'device.display']);
        $request->sortQuery($query, ['hostname' => 'device_hostname', 'mempool_descr', 'mempool_perc', 'mempool_used'], ['hostname' => 'asc', 'mempool_descr' => 'asc']);

        return MempoolResource::collection($request->paginate($query));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $this->authorize('viewAny', Mempool::class);
        $request->validate(['status' => 'nullable|string']);

        $rows = $this->query($request)->with('device')->lazy()->map(function (Mempool $mempool) {
            $isPercent = $mempool->mempool_total == 100;
            $format = fn ($value) => $isPercent ? $value : Number::formatBi($value);

            return [
                $mempool->device_id,
                $mempool->device?->display,
                $mempool->mempool_descr,
                $format($mempool->mempool_used),
                $format($mempool->mempool_free),
                $format($mempool->mempool_total),
                $mempool->mempool_perc . '%',
                $mempool->mempool_perc_warn ?? '-',
            ];
        });

        return $this->streamCsv('mempools', ['Device ID', 'Hostname', 'Description', 'Used', 'Free', 'Total', 'Percentage', 'Warning Threshold'], $rows);
    }

    /**
     * @return Builder<Mempool>
     */
    private function query(DataTableRequest $request): Builder
    {
        return $this->warning($request, Mempool::hasAccess($request->user()));
    }

    /**
     * status=warning: only entries in warning state
     *
     * @param  Builder<Mempool>  $query
     * @return Builder<Mempool>
     */
    private function warning(DataTableRequest $request, Builder $query): Builder
    {
        return $query->when($request->input('status') == 'warning', fn (Builder $q) => $q->where('mempool_perc_warn', '>', 0)
            ->whereColumn('mempool_perc', '>=', 'mempool_perc_warn'));
    }
}
