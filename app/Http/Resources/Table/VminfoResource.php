<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Url;

/**
 * @mixin \App\Models\Vminfo
 */
class VminfoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vmwVmState' => '<span class="label ' . $this->stateLabel[1] . '">' . e($this->stateLabel[0]) . '</span>',
            'vmwVmDisplayName' => is_null($this->parentDevice) ? e($this->vmwVmDisplayName) : Url::modernDeviceLink($this->parentDevice),
            'vm_type' => $this->vm_type,
            'vmwVmGuestOS' => $this->operatingSystem,
            'vmwVmMemSize' => $this->memoryFormatted,
            'vmwVmCpus' => $this->vmwVmCpus,
            'hostname' => Url::modernDeviceLink($this->device),
            'deviceid' => $this->device_id,
            'sysname' => $this->device?->sysName,
            'graphs_url' => route('device.vm.show', ['device' => $this->device_id, 'vminfo' => $this->id]),
        ];
    }
}
