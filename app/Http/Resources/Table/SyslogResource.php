<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;

/**
 * @mixin \App\Models\Syslog
 */
class SyslogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->seq,
            'label' => match ($this->priority) {
                'debug' => 'label-default',
                'info' => 'label-info',
                'notice' => 'label-primary',
                'warning' => 'label-warning',
                'err', 'crit', 'alert', 'emerg' => 'label-danger',
                default => '',
            },
            'timestamp' => $this->timestamp,
            'level' => $this->level,
            'device_id' => Blade::render('<x-device-link :device="$device"/>', ['device' => $this->device]),
            'program' => $this->program,
            'msg' => $this->msg,
            'priority' => $this->priority,
        ];
    }
}
