<?php

namespace App\Http\Resources\Table;

use App\Http\Resources\Table\Concerns\HealthTableCells;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Html;
use LibreNMS\Util\Number;
use LibreNMS\Util\Url;

/**
 * @mixin \App\Models\Storage
 */
class StorageResource extends JsonResource
{
    use HealthTableCells;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $graph = [
            'type' => 'storage_usage',
            'popup_title' => htmlentities(strip_tags($this->device?->display . ': ' . $this->storage_descr)),
            'id' => $this->storage_id,
            'from' => '-1d',
            'height' => 20,
            'width' => 80,
        ];
        $size = Number::formatBi($this->storage_size);
        $free = Number::formatBi($this->storage_free);
        $bar = Html::percentageBar(400, 10, $this->storage_perc, Number::formatBi($this->storage_used) . ' / ' . $size, $free !== $size ? $free : null, $this->storage_perc_warn);

        $cells = [
            'device_hostname' => $this->deviceLink($this->device),
            'storage_descr' => e($this->storage_descr),
            'graph' => Url::graphPopup($graph),
            'storage_used' => Url::graphPopup($graph, $bar),
        ];

        return [
            'id' => $this->storage_id,
            ...($request->input('view') == 'graphs' ? $this->withGraphRow($cells, $graph) : $cells),
            'storage_perc' => round($this->storage_perc) . '%',
        ];
    }
}
