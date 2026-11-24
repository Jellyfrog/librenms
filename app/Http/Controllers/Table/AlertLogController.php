<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\AlertLogResource;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use LibreNMS\Alert\AlertUtil;
use LibreNMS\Util\Time;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Alert log for x-alert-log-table: the alert history page, device faults history tab and alert log widget
 */
class AlertLogController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $query = $this->query($request);
        $request->searchQuery($query, ['device.hostname', 'device.sysName', 'rule.name']);
        $request->sortQuery($query, [
            'time_logged' => 'alert_log.time_logged',
            'status' => 'alert_log.state',
            'alert_rule' => fn (Builder $q, string $direction) => $q->orderBy(AlertRule::select('name')->whereColumn('alert_rules.id', 'alert_log.rule_id'), $direction),
            'severity' => fn (Builder $q, string $direction) => $q->orderBy(AlertRule::select('severity')->whereColumn('alert_rules.id', 'alert_log.rule_id'), $direction),
            'hostname' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('hostname')->whereColumn('devices.device_id', 'alert_log.device_id'), $direction),
        ], ['time_logged' => 'desc']);

        return AlertLogResource::collection($request->paginate($query));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $query = $this->query($request);
        $request->sortQuery($query, ['time_logged' => 'alert_log.time_logged'], ['time_logged' => 'desc']);

        $rows = $query->lazy()->map(fn (AlertLog $log) => [
            $log->id,
            strtolower((string) $log->state->name),
            $log->time_logged->toIso8601ZuluString(),
            $log->device_id,
            $log->device?->display,
            $log->rule_id,
            $log->rule?->name,
            $log->rule?->severity,
            json_encode($log->details),
        ]);

        return $this->streamCsv('alert_log', ['id', 'state', 'time_logged', 'device_id', 'device', 'rule_id', 'rule_name', 'rule_severity', 'details'], $rows);
    }

    /**
     * @return Builder<AlertLog>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', AlertLog::class);

        $request->validate([
            ...AlertLog::filterValidationRules(),
            'severity' => 'array|nullable',
            'severity.*' => 'integer',
            'device_id' => 'integer|nullable',
            'device_group' => 'integer|nullable',
            'state' => 'integer|nullable',
            'from' => 'nullable|date_or_relative',
            'to' => 'nullable|date_or_relative',
        ]);

        $defaultMax = AlertUtil::defaultMaxEntities();

        // Compute the per-group aggregates (representative row id + fault count) once in a
        // single grouped derived table instead of running correlated subqueries per row.
        $groups = DB::table('alert_log')
            ->selectRaw('device_id, rule_id, state, time_logged, min(id) as min_id, sum(case when fault_id is not null then 1 else 0 end) as fault_count')
            ->groupBy('device_id', 'rule_id', 'state', 'time_logged');

        $query = AlertLog::query()
            ->select('alert_log.*')
            ->with(['device', 'rule'])
            ->hasAccess($request->user())
            ->applyFilters($request->filters())
            ->joinSub($groups, 'alert_log_groups', function ($join): void {
                $join->on('alert_log_groups.device_id', '=', 'alert_log.device_id')
                    ->on('alert_log_groups.rule_id', '=', 'alert_log.rule_id')
                    ->on('alert_log_groups.state', '=', 'alert_log.state')
                    ->on('alert_log_groups.time_logged', '=', 'alert_log.time_logged');
            })
            ->whereRaw('(
                alert_log.fault_id is null
                or alert_log.id = alert_log_groups.min_id
                or (
                    coalesce((select ar.notify_per_entity from alert_rules ar where ar.id = alert_log.rule_id), 0) = 1
                    and alert_log_groups.fault_count <= coalesce((select ar.max_entities from alert_rules ar where ar.id = alert_log.rule_id), ?)
                )
            )', [$defaultMax]);

        return $query
            ->when($request->integer('device_id'), fn (Builder $q, int $id) => $q->where('alert_log.device_id', $id))
            ->when($request->filled('state'), fn (Builder $q) => $q->where('alert_log.state', $request->integer('state')))
            ->when(array_filter($request->array('severity')), fn (Builder $q, array $severity) => $q->whereHas('rule', fn (Builder $rq) => $rq->whereIn('severity', array_map(intval(...), $severity))))
            ->when($request->integer('device_group'), fn (Builder $q, int $group) => $q->inDeviceGroup($group))
            ->when(Time::parseInput($request->input('from')), fn (Builder $q, int $from) => $q->whereRaw('alert_log.time_logged >= FROM_UNIXTIME(?)', [$from]))
            ->when(Time::parseInput($request->input('to')), fn (Builder $q, int $to) => $q->whereRaw('alert_log.time_logged <= FROM_UNIXTIME(?)', [$to]));
    }
}
