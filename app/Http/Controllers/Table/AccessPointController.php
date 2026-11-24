<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\AccessPointResource;
use App\Models\AccessPoint;
use App\Models\Device;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccessPointController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $request->validate(['device_id' => 'required|integer|exists:devices,device_id']);
        $device = Device::findOrFail($request->integer('device_id'));
        $this->authorize('view', $device);

        $query = AccessPoint::query()
            ->where('device_id', $device->device_id)
            ->where('deleted', false);
        $request->searchQuery($query, ['name', 'mac_addr', 'type']);
        $request->sortQuery($query, [
            'name' => fn (Builder $q, string $direction) => $q->orderBy('name', $direction)->orderBy('radio_number', $direction),
            'radio' => fn (Builder $q, string $direction) => $q->orderBy('radio_number', $direction)->orderBy('type', $direction),
            'channel', 'numasoclients', 'radioutil', 'interference', 'txpow',
        ], ['name' => 'asc']);

        return AccessPointResource::collection($request->paginate($query));
    }
}
