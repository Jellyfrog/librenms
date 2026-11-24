<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\AlertFaultResource;
use App\Models\Alert;
use App\Models\AlertFault;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LibreNMS\Alert\AlertUtil;
use LibreNMS\Enum\AlertState;

/**
 * Open alerts (faults) for x-alerts-table: the alerts page, device faults tab and alerts widget
 */
class AlertsController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Alert::class);

        $request->validate([
            ...AlertFault::filterValidationRules(),
            'rule_id' => 'nullable|integer|min:1',
            'alert_id' => 'nullable|integer',
            'device_id' => 'nullable|integer',
            'acknowledged' => 'nullable|integer|in:0,1',
            'fired' => 'nullable|integer|in:0,1',
            'unreachable' => 'nullable|integer|in:0,1',
            'state' => 'nullable|integer',
            'min_severity' => 'nullable|string',
            'group' => 'nullable|integer|min:1',
            'uncollapse_key_count' => 'nullable|integer',
        ]);

        $query = AlertFault::query()
            ->select('alert_faults.*')
            ->with(['device', 'device.location', 'rule'])
            ->where('alert_faults.open', 1)
            ->whereHas('device', fn (Builder $q) => $q->where('disabled', 0))
            ->whereHas('rule', fn (Builder $q) => $q->where('disabled', 0))
            ->hasAccess($request->user())
            ->applyFilters($request->filters())
            ->when($request->integer('rule_id'), fn (Builder $q, int $id) => $q->where('alert_faults.rule_id', $id))
            ->when($request->integer('alert_id'), fn (Builder $q, int $id) => $q->where('alert_faults.id', $id))
            ->when($request->integer('device_id'), fn (Builder $q, int $id) => $q->where('alert_faults.device_id', $id))
            ->when($request->filled('acknowledged'), fn (Builder $q) => $q->where('alert_faults.state', $request->boolean('acknowledged') ? '=' : '!=', AlertState::ACKNOWLEDGED))
            ->when($request->boolean('fired'), fn (Builder $q) => $q->where('alert_faults.state', AlertState::ACTIVE))
            ->when($request->filled('unreachable'), fn (Builder $q) => $q->unreachable($request->boolean('unreachable')))
            ->when($request->filled('state'), fn (Builder $q) => $q->where('alert_faults.state', $request->integer('state')))
            ->when($request->integer('group'), fn (Builder $q, int $group) => $q->inDeviceGroup($group))
            ->when($request->input('min_severity'), fn (Builder $q, string $severity) => $q->whereHas('rule', fn (Builder $rq) => $rq->minSeverity($severity)));

        if (! $request->filled('state') || $request->integer('state') !== AlertState::RECOVERED) {
            $query->where('alert_faults.state', '!=', AlertState::RECOVERED);
        }

        // Per-entity rules show every fault until the entity ceiling; then collapse like grouped rules.
        $openCountSql = AlertUtil::sqlOpenFaultCountForRow();
        $defaultMax = AlertUtil::defaultMaxEntities();
        $query->whereRaw('(
            (
                coalesce((select ar.notify_per_entity from alert_rules ar where ar.id = alert_faults.rule_id), 0) = 1
                and ' . $openCountSql . ' <= coalesce((select ar.max_entities from alert_rules ar where ar.id = alert_faults.rule_id), ?)
            )
            or alert_faults.id = (
                select min(f2.id) from alert_faults f2
                where f2.device_id = alert_faults.device_id
                  and f2.rule_id = alert_faults.rule_id
                  and f2.open = 1
                  and f2.state != ?
            )
        )', [$defaultMax, AlertState::RECOVERED]);

        $request->searchQuery($query, ['timestamp', 'rule.builder', 'rule.name', 'device.hostname', 'device.sysName']);
        $request->sortQuery($query, [
            'timestamp' => 'alert_faults.timestamp',
            'severity' => fn (Builder $q, string $direction) => $q->orderBy(AlertRule::select('severity')->whereColumn('alert_rules.id', 'alert_faults.rule_id'), $direction)->orderBy('alert_faults.timestamp', $direction),
            'rule' => fn (Builder $q, string $direction) => $q->orderBy(AlertRule::select('name')->whereColumn('alert_rules.id', 'alert_faults.rule_id'), $direction),
            'hostname' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('hostname')->whereColumn('devices.device_id', 'alert_faults.device_id'), $direction),
            'location' => fn (Builder $q, string $direction) => $q->orderBy(Location::select('location')->join('devices', 'devices.location_id', 'locations.id')->whereColumn('devices.device_id', 'alert_faults.device_id'), $direction),
        ], ['timestamp' => 'desc']);

        return AlertFaultResource::collection($request->paginate($query));
    }
}
