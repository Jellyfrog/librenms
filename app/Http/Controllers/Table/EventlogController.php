<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\EventlogResource;
use App\Models\Eventlog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventlogController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Eventlog::class);

        $request->validate([
            'device' => 'nullable|integer',
            'device_group' => 'nullable|integer',
            'age' => 'nullable|integer|min:1',
            ...Eventlog::filterValidationRules(),
        ]);

        $query = Eventlog::hasAccess($request->user())
            ->with('device')
            ->applyFilters($request->filters())
            ->when($request->integer('device'), fn (Builder $q, int $deviceId) => $q->where('device_id', $deviceId))
            ->when($request->integer('device_group'), fn (Builder $q, int $groupId) => $q->inDeviceGroup($groupId))
            ->when($request->integer('age'), fn (Builder $q, int $age) => $q->where('datetime', '>', now()->subSeconds($age)))
            ->when($request->search(), fn (Builder $q, string $search) => $q->where('message', 'like', "%$search%"));

        $request->sortQuery($query, ['datetime', 'type', 'device_id', 'message', 'username'], ['datetime' => 'desc']);

        return EventlogResource::collection($request->paginate($query));
    }
}
