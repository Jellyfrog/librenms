<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\SensorResource;
use App\Models\Sensor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use LibreNMS\Enum\SensorState;
use LibreNMS\Enum\SensorType;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SensorsController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $query = $this->query($request)->with('device');

        return SensorResource::collection($request->paginate($query));
    }

    public function export(DataTableRequest $request, ?string $class = null): StreamedResponse
    {
        if ($class) {
            $request->merge(['class' => $class]);
        }

        $rows = $request->exportRows($this->query($request)->with('device'))->map(fn (Sensor $sensor) => [
            $sensor->device?->display,
            $sensor->sensor_descr,
            $sensor->formatValue(),
            $sensor->formatValue('sensor_limit_low'),
            $sensor->formatValue('sensor_limit'),
            $sensor->sensor_class instanceof \BackedEnum ? $sensor->sensor_class->value : $sensor->sensor_class,
            $sensor->sensor_type,
        ]);

        return $this->streamCsv('sensors', ['Device Hostname', 'Sensor', 'Current', 'Limit Low', 'Limit High', 'Sensor Class', 'Sensor Type'], $rows);
    }

    /**
     * @return Builder<Sensor>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', Sensor::class);
        $request->validate([
            'view' => ['nullable', Rule::in(['detail', 'graphs'])],
            'class' => ['nullable', Rule::in([...SensorType::values(), 'all'])],
            'status' => 'nullable|string',
        ]);

        $class = $request->input('class', 'all');
        $status = $request->input('status');

        $query = Sensor::query()
            ->hasAccess($request->user())
            ->when($class != 'all', fn (Builder $q) => $q->where('sensor_class', $class))
            ->when($class == 'state' || $class == 'all', fn (Builder $q) => $q->with('translations'))
            ->withAggregate('device', 'hostname')
            ->when($status == 'unknown', fn (Builder $q) => (new Sensor)->scopeStateUnknown($q))
            ->when($status == 'alert', fn (Builder $q) => $q->where('sensor_alert', 1))
            ->when(in_array($status, ['alert', 'error']), fn (Builder $q) => $q->where(fn (Builder $q) => (new Sensor)->scopeIsCritical($q)
                ->orWhere(fn (Builder $q) => (new Sensor)->scopeStateEq($q, SensorState::Error))))
            ->when($status == 'warning', fn (Builder $q) => $q->where(fn (Builder $q) => (new Sensor)->scopeStateEq($q, SensorState::Warning)
                ->orWhere(function (Builder $q): void {
                    $q->whereNot(fn (Builder $q) => (new Sensor)->scopeIsCritical($q));
                    (new Sensor)->scopeIsWarning($q);
                })));
        $request->searchQuery($query, ['sensor_descr', 'sensor_current', 'device.hostname', 'device.display']);

        return $request->sortQuery($query, ['device_hostname', 'sensor_descr', 'sensor_current', 'sensor_limit_low', 'sensor_limit'], ['device_hostname' => 'asc', 'sensor_descr' => 'asc']);
    }
}
