<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\SyslogResource;
use App\Models\Syslog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LibreNMS\Enum\SyslogSeverity;

class SyslogController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Syslog::class);

        $request->validate([
            'device' => 'nullable|integer',
            'device_group' => 'nullable|integer',
            'level' => 'nullable|integer|between:0,7',
            ...Syslog::filterValidationRules(),
        ]);

        $query = Syslog::hasAccess($request->user())
            ->with('device')
            ->applyFilters($request->filters())
            ->when($request->integer('device'), fn (Builder $q, int $deviceId) => $q->where('device_id', $deviceId))
            ->when($request->integer('device_group'), fn (Builder $q, int $groupId) => $q->inDeviceGroup($groupId))
            // widget: this level and more severe
            ->when($request->filled('level') && $request->integer('level') < 7, fn (Builder $q) => $q->whereIn('level', array_slice(SyslogSeverity::LEVELS, 0, $request->integer('level') + 1)))
            ->when($request->search(), fn (Builder $q, string $search) => $q->where('msg', 'like', "%$search%"));

        $request->sortQuery($query, ['timestamp', 'level', 'device_id', 'program', 'msg', 'priority'], ['timestamp' => 'desc']);

        return SyslogResource::collection($request->paginate($query));
    }
}
