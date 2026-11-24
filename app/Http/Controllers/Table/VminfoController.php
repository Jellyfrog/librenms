<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\VminfoResource;
use App\Models\Device;
use App\Models\Vminfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VminfoController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        return VminfoResource::collection($request->paginate($this->query($request)));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $rows = $request->exportRows($this->query($request))->map(fn (Vminfo $vm) => [
            $vm->stateLabel[0],
            is_null($vm->parentDevice) ? $vm->vmwVmDisplayName : $vm->parentDevice->display,
            $vm->vm_type,
            $vm->operatingSystem,
            $vm->memoryFormatted,
            $vm->vmwVmCpus,
            $vm->device?->display,
            $vm->device_id,
            $vm->device?->sysName,
        ]);

        return $this->streamCsv('vminfo', [
            __('Power Status'), __('VM Name'), __('Type'), __('Operating System'), __('Memory'), __('vCPUs'), __('Host'), __('Device Id'), __('Sysname'),
        ], $rows);
    }

    /**
     * @return Builder<Vminfo>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', Vminfo::class);
        $request->validate([
            'device_id' => 'nullable|integer',
            ...Vminfo::filterValidationRules(),
        ]);

        $query = Vminfo::hasAccess($request->user())
            ->select('vminfo.*')
            ->with(['device', 'parentDevice'])
            ->when($request->integer('device_id'), fn (Builder $q, int $deviceId) => $q->where('vminfo.device_id', $deviceId))
            ->applyFilters($request->filters());
        $request->searchQuery($query, ['vmwVmDisplayName', 'vmwVmGuestOS', 'device.hostname', 'device.sysName']);

        return $request->sortQuery($query, [
            'vmwVmDisplayName', 'vmwVmGuestOS', 'vmwVmMemSize', 'vmwVmCpus', 'vmwVmState', 'vm_type',
            'hostname' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('hostname')->whereColumn('devices.device_id', 'vminfo.device_id'), $direction),
        ], ['vmwVmDisplayName' => 'asc']);
    }
}
