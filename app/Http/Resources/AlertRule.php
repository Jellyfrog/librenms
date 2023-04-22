<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Device $devices
 * @property \App\Models\DeviceGroup $groups
 * @property \App\Models\Location $locations
 */
class AlertRule extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(Request $request): array
    {
        $rule = parent::toArray($request);
        $rule['devices'] = $this->devices->pluck('device_id')->all();
        $rule['groups'] = $this->groups->pluck('id')->all();
        $rule['locations'] = $this->locations->pluck('id')->all();

        return $rule;
    }
}
