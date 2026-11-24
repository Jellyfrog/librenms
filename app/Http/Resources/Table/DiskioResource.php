<?php

namespace App\Http\Resources\Table;

use App\Http\Resources\Table\Concerns\HealthTableCells;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Url;

/**
 * @mixin \App\Models\UcdDiskio
 */
class DiskioResource extends JsonResource
{
    use HealthTableCells;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $graph = [
            'type' => 'diskio_bits',
            'popup_title' => htmlentities(strip_tags($this->device?->display . ': ' . $this->diskio_descr)),
            'id' => $this->diskio_id,
            'from' => '-1d',
            'height' => 20,
            'width' => 80,
        ];

        return [
            'id' => $this->diskio_id,
            'device_hostname' => $this->deviceLink($this->device),
            'diskio_descr' => $this->diskio_descr,
            'bits_graph' => Url::graphPopup($graph),
            'ops_graph' => Url::graphPopup(['type' => 'diskio_ops'] + $graph),
        ];
    }
}
