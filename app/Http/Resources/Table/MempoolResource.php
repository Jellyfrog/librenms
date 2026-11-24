<?php

namespace App\Http\Resources\Table;

use App\Facades\LibrenmsConfig;
use App\Http\Resources\Table\Concerns\HealthTableCells;
use App\Models\Device;
use App\Models\Mempool;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use LibreNMS\Util\Html;
use LibreNMS\Util\Number;
use LibreNMS\Util\Url;

/**
 * Memory pool row, the graphs view lists devices with one graph row each
 *
 * @property Mempool|Device $resource
 */
class MempoolResource extends JsonResource
{
    use HealthTableCells;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof Device) {
            $graphs = Html::graphRow(['device' => $this->resource->device_id, 'type' => 'device_mempool', 'height' => 100, 'width' => 216]);

            return [
                'id' => $this->resource->device_id,
                'hostname' => $this->deviceLink($this->resource),
                'mempool_descr' => $graphs[0],
                'graph' => $graphs[1],
                'mempool_used' => $graphs[2],
                'mempool_perc' => $graphs[3],
            ];
        }

        $mempool = $this->resource;

        return [
            'id' => $mempool->mempool_id,
            'hostname' => $this->deviceLink($mempool->device),
            'mempool_descr' => e($mempool->mempool_descr),
            'graph' => $this->miniGraph($mempool),
            'mempool_used' => $this->barLink($mempool),
            'mempool_perc' => $mempool->mempool_perc . '%',
        ];
    }

    private function miniGraph(Mempool $mempool): string
    {
        $graph = ['type' => 'mempool_usage', 'id' => $mempool->mempool_id, 'from' => LibrenmsConfig::get('time.day'), 'height' => 20, 'width' => 80];
        $link = Url::generate(['page' => 'graphs'], Arr::only($graph, ['id', 'type', 'from']));

        return Url::overlibLink($link, Url::graphTag($graph), Url::graphTag(['height' => 150, 'width' => 400] + $graph));
    }

    private function barLink(Mempool $mempool): string
    {
        $graph = ['type' => 'mempool_usage', 'id' => $mempool->mempool_id, 'from' => LibrenmsConfig::get('time.day'), 'height' => 150, 'width' => 400];

        $isPercent = $mempool->mempool_total == 100;
        $free = $isPercent ? $mempool->mempool_free : Number::formatBi($mempool->mempool_free);
        $used = $isPercent ? $mempool->mempool_used : Number::formatBi($mempool->mempool_used);
        $total = $isPercent ? $mempool->mempool_total : Number::formatBi($mempool->mempool_total);

        $percent = Html::percentageBar(400, 10, $mempool->mempool_perc, "$used / $total", $free, $mempool->mempool_perc_warn);
        $link = Url::generate(['page' => 'graphs'], Arr::only($graph, ['id', 'type', 'from']));

        return Url::overlibLink($link, $percent, Url::graphTag($graph));
    }
}
