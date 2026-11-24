<?php

namespace App\Http\Resources\Table;

use App\Http\Resources\Table\Concerns\HealthTableCells;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Color;
use LibreNMS\Util\Html;
use LibreNMS\Util\Number;
use LibreNMS\Util\StringHelpers;
use LibreNMS\Util\Url;

/**
 * @mixin \App\Models\PrinterSupply
 */
class PrinterSupplyResource extends JsonResource
{
    use HealthTableCells;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $percent = Number::calculatePercent($this->supply_current, $this->supply_capacity);
        $graph = [
            'type' => 'toner_usage',
            'popup_title' => htmlentities(strip_tags($this->device?->display . ': ' . $this->supply_descr)),
            'id' => $this->supply_id,
            'from' => '-1d',
            'height' => 20,
            'width' => 80,
        ];

        if ($request->input('view') == 'graphs') {
            return [
                'id' => $this->supply_id,
                ...$this->withGraphRow([
                    'device_hostname' => $this->deviceLink($this->device),
                    'supply_descr' => e($this->supply_descr),
                    'graph' => Url::graphPopup($graph),
                    'supply_current' => $percent . '%',
                ], $graph),
                'supply_type' => '',
            ];
        }

        // colored by supply left, not usage
        $bar = Html::percentageBar(400, 10, $percent, $this->supply_current, $this->supply_capacity == 100 ? '' : $this->supply_capacity, colors: Color::percentage(100 - $percent));

        return [
            'id' => $this->supply_id,
            'device_hostname' => $this->deviceLink($this->device),
            'supply_descr' => e($this->supply_descr),
            'supply_type' => StringHelpers::camelToTitle($this->supply_type == 'opc' ? 'organicPhotoConductor' : $this->supply_type),
            'graph' => Url::graphPopup($graph),
            'supply_current' => Url::graphPopup($graph, $bar),
        ];
    }
}
