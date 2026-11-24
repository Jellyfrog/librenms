<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\ProcessorResource;
use App\Models\Processor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcessorsController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $query = $this->query($request)->with('device');

        return ProcessorResource::collection($request->paginate($query));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $rows = $request->exportRows($this->query($request)->with('device'))->map(fn (Processor $processor) => [
            $processor->device?->display,
            $processor->processor_descr,
            $processor->processor_usage,
        ]);

        return $this->streamCsv('processors', ['Device Hostname', 'Processor', 'Usage'], $rows);
    }

    /**
     * @return Builder<Processor>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', Processor::class);
        $request->validate(['status' => 'nullable|string', 'view' => 'nullable|in:detail,graphs']);

        $query = Processor::query()
            ->hasAccess($request->user())
            ->withAggregate('device', 'hostname')
            // only entries in warning state
            ->when($request->input('status') == 'warning', fn (Builder $q) => $q->where('processor_perc_warn', '>', 0)
                ->whereColumn('processor_usage', '>=', 'processor_perc_warn'));
        $request->searchQuery($query, ['processor_descr', 'device.hostname', 'device.display']);

        return $request->sortQuery($query, ['device_hostname', 'processor_descr', 'processor_usage'], ['device_hostname' => 'asc', 'processor_descr' => 'asc']);
    }
}
