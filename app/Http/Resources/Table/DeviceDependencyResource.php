<?php

namespace App\Http\Resources\Table;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Url;

/**
 * A device and its parent devices
 *
 * @mixin Device
 */
class DeviceDependencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $parents = $this->parents->sortBy('hostname');

        return [
            'device_id' => $this->device_id,
            'hostname' => Url::modernDeviceLink($this->resource, extra: $this->sysName ?? ''),
            'parents' => $parents->isEmpty()
                ? e(__('None'))
                : $parents->map(fn (Device $parent) => Url::modernDeviceLink($parent))->implode(', '),
            'parent_list' => $parents->map(fn (Device $parent) => ['id' => $parent->device_id, 'text' => $parent->display])->values(),
            'display_name' => $this->display,
        ];
    }
}
