<?php

namespace App\Http\Resources\Table;

use App\Models\Sensor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Enum\SensorType;
use LibreNMS\Enum\Severity;
use LibreNMS\Util\Time;
use LibreNMS\Util\Url;

/**
 * Eventlog table row, html cells for x-eventlog-table
 *
 * @mixin \App\Models\Eventlog
 */
class EventlogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->event_id,
            'datetime' => '<span class="alert-status ' . $this->severityLabel() . ' eventlog-status"></span>' . Time::format($this->datetime, 'compact'),
            'device_id' => Blade::render('<x-device-link :device="$device"/>', ['device' => $this->device]),
            'type' => $this->type(),
            'message' => $this->message,
            'username' => $this->username ?: 'System',
        ];
    }

    private function type(): string
    {
        if ($this->type == 'interface' && is_numeric($this->reference) && $this->related) {
            return Blade::render('<b><x-port-link :port="$port">{{ $port->getShortLabel() }}</x-port-link></b>', ['port' => $this->related]);
        }

        if ($this->type == 'stp') {
            return Blade::render('<x-device-link :device="$device" tab="stp">stp</x-device-link>', ['device' => $this->device]);
        }

        if (in_array($this->type, SensorType::values()) && is_numeric($this->reference) && $this->related instanceof Sensor) {
            return '<b>' . Url::sensorLink($this->related, $this->related->sensor_descr) . '</b>';
        }

        return e($this->type);
    }

    private function severityLabel(): string
    {
        return match ($this->severity) {
            Severity::Ok => 'label-success',
            Severity::Info => 'label-info',
            Severity::Notice => 'label-primary',
            Severity::Warning => 'label-warning',
            Severity::Error => 'label-danger',
            default => 'label-default',
        };
    }
}
