<?php

namespace App\Http\Resources\Table;

use App\Http\Resources\Table\Concerns\HealthTableCells;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Enum\Severity;
use LibreNMS\Util\Html;
use LibreNMS\Util\Url;

/**
 * Sensor or wireless sensor row of the health tables, view=graphs adds a graph row
 *
 * @mixin \App\Models\SensorModel
 */
class SensorResource extends JsonResource
{
    use HealthTableCells;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $graph = [
            'type' => $this->getGraphType(),
            'popup_title' => htmlentities(strip_tags($this->device?->display . ': ' . $this->sensor_descr)),
            'id' => $this->sensor_id,
            'from' => '-1d',
            'height' => 20,
            'width' => 80,
        ];
        $class = $this->sensor_class instanceof \BackedEnum ? $this->sensor_class->value : $this->sensor_class;
        $link = Url::generate(['page' => 'device', 'device' => $this->device_id, 'tab' => 'health', 'metric' => $class]);
        $status = $this->currentStatus();

        $cells = [
            'device_hostname' => $this->deviceLink($this->device),
            'sensor_descr' => Url::graphPopup($graph, htmlentities((string) $this->sensor_descr), $link),
            'graph' => Url::graphPopup($graph),
            'sensor_current' => Html::severityToLabel($status, $this->formatValue()),
        ];

        if ($request->input('view') == 'graphs') {
            $cells = $this->withGraphRow($cells, $graph, 210);
        }

        return [
            'id' => $this->sensor_id,
            ...$cells,
            'alert' => $status == Severity::Error ? '<i class="fa fa-flag fa-lg" style="color:red" aria-hidden="true"></i>' : '',
            'sensor_limit_low' => Html::severityToLabel(Severity::Unknown, $this->formatValue('sensor_limit_low')),
            'sensor_limit' => Html::severityToLabel(Severity::Unknown, $this->formatValue('sensor_limit')),
        ];
    }
}
