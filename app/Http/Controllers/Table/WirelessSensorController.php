<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\SensorResource;
use App\Models\WirelessSensor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use LibreNMS\Enum\WirelessSensorType;

class WirelessSensorController extends Controller
{
    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WirelessSensor::class);
        $request->validate([
            'view' => ['nullable', Rule::in(['detail', 'graphs'])],
            'class' => ['required', Rule::in(WirelessSensorType::values())],
        ]);

        $query = WirelessSensor::query()
            ->hasAccess($request->user())
            ->with('device')
            ->where('sensor_class', $request->input('class'))
            ->withAggregate('device', 'hostname');
        $request->searchQuery($query, ['sensor_descr', 'sensor_current', 'device.hostname', 'device.display']);
        $request->sortQuery($query, ['device_hostname', 'sensor_descr', 'sensor_current', 'sensor_limit_low', 'sensor_limit'], ['device_hostname' => 'asc', 'sensor_descr' => 'asc']);

        return SensorResource::collection($request->paginate($query));
    }
}
