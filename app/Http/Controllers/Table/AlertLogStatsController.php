<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\AlertLogStatsResource;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LibreNMS\Enum\AlertState;

/**
 * How often each rule alerted on each device, for the alert log stats widget
 */
class AlertLogStatsController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AlertLog::class);
        $request->validate([
            'device_id' => 'nullable|integer',
            'min_severity' => 'nullable|string',
            'time_interval' => 'nullable|integer|min:1',
        ]);

        $query = AlertLog::hasAccess($request->user())
            ->selectRaw('count(*) as count, alert_log.device_id, alert_log.rule_id')
            ->with(['device', 'rule'])
            ->where('alert_log.state', AlertState::ACTIVE)
            ->when($request->integer('device_id'), fn (Builder $q, int $id) => $q->where('alert_log.device_id', $id))
            ->when($request->integer('time_interval'), fn (Builder $q, int $days) => $q->where('alert_log.time_logged', '>', now()->subDays($days)))
            ->when($request->input('min_severity'), fn (Builder $q, string $severity) => $q->whereHas('rule', fn (Builder $rq) => $rq->minSeverity($severity)))
            ->groupBy('alert_log.device_id', 'alert_log.rule_id');

        $request->searchQuery($query, ['device.hostname', 'device.sysName', 'rule.name']);
        $request->sortQuery($query, [
            'count' => 'count',
            'hostname' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('hostname')->whereColumn('devices.device_id', 'alert_log.device_id'), $direction),
            'alert_rule' => fn (Builder $q, string $direction) => $q->orderBy(AlertRule::select('name')->whereColumn('alert_rules.id', 'alert_log.rule_id'), $direction),
        ], ['count' => 'desc']);

        return AlertLogStatsResource::collection($request->paginate($query));
    }
}
