<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;

/**
 * @mixin \App\Models\AlertLog
 */
class AlertLogStatsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->device_id . '-' . $this->rule_id,
            'count' => (int) $this->getAttribute('count'),
            'hostname' => Blade::render('<x-device-link :device="$device"/>', ['device' => $this->device]),
            'alert_rule' => $this->rule?->name,
        ];
    }
}
