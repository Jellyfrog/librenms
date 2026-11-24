<?php

namespace App\Http\Resources\Table;

use App\Http\Resources\Table\Concerns\HealthTableCells;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Html;
use LibreNMS\Util\Url;

/**
 * @mixin \App\Models\Processor
 */
class ProcessorResource extends JsonResource
{
    use HealthTableCells;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $perc = (int) round($this->processor_usage ?? 0);
        $graph = [
            'type' => 'processor_usage',
            'popup_title' => htmlentities(strip_tags($this->device?->display . ': ' . $this->processor_descr)),
            'id' => $this->processor_id,
            'from' => '-1d',
            'height' => 20,
            'width' => 80,
        ];
        $bar = $this->processor_usage === null
            ? Html::percentageBar(400, 10, 0, '?', '', $this->processor_perc_warn)
            : Html::percentageBar(400, 10, $perc, $perc . '%', (100 - $perc) . '%', $this->processor_perc_warn);

        $cells = [
            'device_hostname' => $this->deviceLink($this->device),
            'processor_descr' => e($this->processor_descr),
            'graph' => Url::graphPopup($graph),
            'processor_usage' => Url::graphPopup($graph, $bar),
        ];

        return ['id' => $this->processor_id, ...($request->input('view') == 'graphs' ? $this->withGraphRow($cells, $graph) : $cells)];
    }
}
