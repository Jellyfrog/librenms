<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\TnmsneResource;
use App\Models\Device;
use App\Models\TnmsneInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TnmsneController extends Controller
{
    private const array COLUMNS = ['neName', 'neLocation', 'neType', 'neOpMode', 'neAlarm', 'neOpState'];

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Device::class);
        $request->validate(['device_id' => 'nullable|integer']);

        $query = TnmsneInfo::hasAccess($request->user())
            ->when($request->integer('device_id'), fn (Builder $q, int $deviceId) => $q->where('device_id', $deviceId));
        $request->searchQuery($query, self::COLUMNS);
        $request->sortQuery($query, self::COLUMNS, ['neName' => 'asc']);

        return TnmsneResource::collection($request->paginate($query));
    }
}
