<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Html;

/**
 * Location row, graphs are shown when the row is expanded
 *
 * @mixin \App\Models\Location
 */
class LocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'devices' => $this->devices_count,
            'down' => $this->getAttribute('down_count'),
            'graphs' => $this->devices_count > 0
                ? Html::graphRow(['type' => 'location_bits', 'height' => '100', 'width' => '220', 'legend' => 'no', 'id' => $this->id])
                : [],
        ];
    }
}
