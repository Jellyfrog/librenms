<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\AlertScheduleResource;
use App\Models\AlertSchedule;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AlertScheduleController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AlertSchedule::class);

        $query = AlertSchedule::query();
        $request->searchQuery($query, ['title', 'start', 'end']);
        $request->sortQuery($query, [
            'start_recurring_dt' => DB::raw('DATE(`start`)'),
            'start_recurring_hr' => DB::raw('TIME(`start`)'),
            'end_recurring_dt' => DB::raw('DATE(`end`)'),
            'end_recurring_hr' => DB::raw('TIME(`end`)'),
            'title' => 'title',
            'behavior' => 'behavior',
            'recurring' => 'recurring',
            'start' => 'start',
            'end' => 'end',
            // only partition lapsed
            'status' => fn ($q, string $direction) => $q->orderByRaw('`end` < ? ' . $direction, [Carbon::now('UTC')]),
        ], ['start' => 'desc']);

        return AlertScheduleResource::collection($request->paginate($query));
    }
}
