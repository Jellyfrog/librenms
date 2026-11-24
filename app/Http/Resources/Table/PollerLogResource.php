<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Time;
use LibreNMS\Util\Url;

/**
 * @mixin \App\Models\Device
 */
class PollerLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->device_id,
            'hostname' => Url::deviceLink($this->resource, null, ['tab' => 'graphs', 'group' => 'poller']),
            'last_polled' => $this->last_polled ? Time::format($this->last_polled, 'compact') : '',
            'poller_group' => $this->pollerGroup?->group_name ?? __('General'),
            'location' => $this->location?->location,
            'last_polled_timetaken' => round((float) $this->last_polled_timetaken, 2),
        ];
    }
}
