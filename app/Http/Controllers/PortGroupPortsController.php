<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\PortGroupPortResource;
use App\Models\Device;
use App\Models\Port;
use App\Models\PortGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class PortGroupPortsController extends Controller
{
    private const array MEMBERSHIP = ['members', 'available', 'all'];

    public function index(Request $request, PortGroup $portGroup): View
    {
        $this->authorize('update', $portGroup);
        $request->validate(Port::filterValidationRules());

        $members = $this->memberCount($request, $portGroup);

        return view('port-group.ports', [
            'port_group' => $portGroup,
            'members' => $members,
            'membership' => $members > 0 ? 'members' : 'available',
            'filter' => $request->array('filter'),
            'filter_fields' => $this->filterFields(),
        ]);
    }

    public function ports(DataTableRequest $request, PortGroup $portGroup): AnonymousResourceCollection
    {
        $this->authorize('update', $portGroup);

        $request->validate(Port::filterValidationRules() + [
            'membership' => 'nullable|in:' . implode(',', self::MEMBERSHIP),
        ]);

        $membership = $request->input('membership', 'members');
        $query = $this->filteredQuery($request, $portGroup, $membership)
            ->with('device')
            // membership only varies on the all tab
            ->when($membership === 'all', fn (Builder $q) => $q->withExists(['groups as member' => fn (Builder $q) => $q->where('port_groups.id', $portGroup->id)]));

        $request->sortQuery($query, [
            // sort by the displayed device name
            'device' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('display')->whereColumn('devices.device_id', 'ports.device_id'), $direction),
            'ifName',
            'ifOperStatus',
            'ifSpeed',
            'ifAlias',
        ], ['device' => 'asc']);
        $query->orderBy('ports.device_id')->orderBy('ifIndex');

        return PortGroupPortResource::collection($request->paginate($query))
            ->additional(['members' => $this->memberCount($request, $portGroup)]);
    }

    public function update(Request $request, PortGroup $portGroup): JsonResponse
    {
        $this->authorize('update', $portGroup);

        $validated = $request->validate(Port::filterValidationRules() + [
            'action' => 'required|in:add,remove',
            'membership' => 'nullable|in:' . implode(',', self::MEMBERSHIP),
            'all' => 'required_without:ids|boolean',
            'ids' => 'required_unless:all,true|array',
            'ids.*' => 'integer',
        ]);

        // either every port matching the filter or the selected ports, always limited to ports the user can access
        $query = empty($validated['all'])
            ? Port::hasAccess($request->user())->whereIn('ports.port_id', $validated['ids'])
            : $this->filteredQuery($request, $portGroup, $validated['membership'] ?? 'members');

        $count = 0;
        $query->select('ports.port_id')->chunkById(1000, function ($ports) use ($portGroup, $validated, &$count): void {
            $portIds = $ports->pluck('port_id');
            $count += $validated['action'] === 'add'
                ? count($portGroup->ports()->syncWithoutDetaching($portIds)['attached'])
                : $portGroup->ports()->detach($portIds);
        }, 'ports.port_id', 'port_id');

        return response()->json([
            'message' => trans_choice($validated['action'] === 'add' ? 'port.groups.added' : 'port.groups.removed', $count),
            'updated' => $count,
            'members' => $this->memberCount($request, $portGroup),
        ]);
    }

    /**
     * @return Builder<Port>
     */
    private function filteredQuery(Request $request, PortGroup $portGroup, string $membership): Builder
    {
        return Port::hasAccess($request->user())
            ->when($membership === 'members', fn (Builder $q) => $q->inPortGroup($portGroup->id))
            ->when($membership === 'available', fn (Builder $q) => $q->notInPortGroup($portGroup->id))
            ->applyFilters($request->array('filter'));
    }

    /**
     * Group members the user can access, matching the members tab
     */
    private function memberCount(Request $request, PortGroup $portGroup): int
    {
        return Port::hasAccess($request->user())->inPortGroup($portGroup->id)->count();
    }

    /**
     * @return list<array{key: string, label: string, type: string, search?: bool, endpoint?: string, options?: array<string, string>, params?: array<string, string>}>
     */
    private function filterFields(): array
    {
        return [
            ['key' => 'search', 'label' => __('port.settings.filter_search'), 'type' => 'text', 'search' => true],
            ['key' => 'device_id', 'label' => __('Device'), 'type' => 'select', 'endpoint' => route('ajax.select.device')],
            ['key' => 'device.hostname', 'label' => __('Hostname'), 'type' => 'text'],
            ['key' => 'device.groups.id', 'label' => __('device.device_group'), 'type' => 'select', 'endpoint' => route('ajax.select.device-group')],
            ['key' => 'device.location_id', 'label' => __('Location'), 'type' => 'select', 'endpoint' => route('ajax.select.location')],
            ['key' => 'groups.id', 'label' => __('port.port_group'), 'type' => 'select', 'endpoint' => route('ajax.select.port-group')],
            ['key' => 'state', 'label' => __('port.oper_status'), 'type' => 'select', 'options' => [
                'up' => __('Up'),
                'down' => __('Down'),
                'shutdown' => __('Shutdown'),
            ]],
            ['key' => 'ifSpeed', 'label' => __('port.speed'), 'type' => 'select', 'endpoint' => route('ajax.select.port-field'), 'params' => ['field' => 'ifSpeed']],
            ['key' => 'ifType', 'label' => __('port.media'), 'type' => 'select', 'endpoint' => route('ajax.select.port-field'), 'params' => ['field' => 'ifType']],
            ['key' => 'disabled', 'label' => __('Disabled'), 'type' => 'boolean'],
            ['key' => 'ignore', 'label' => __('Ignored'), 'type' => 'boolean'],
            ['key' => 'deleted', 'label' => __('Deleted'), 'type' => 'boolean'],
        ];
    }
}
