<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Url;

/**
 * @mixin \App\Models\EntPhysical
 */
class InventoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->entPhysical_id,
            'device' => Url::modernDeviceLink($this->device),
            'descr' => $this->entPhysicalDescr,
            'name' => $this->entPhysicalName,
            'model' => $this->entPhysicalModelName,
            'serial' => $this->entPhysicalSerialNum,
        ];
    }
}
