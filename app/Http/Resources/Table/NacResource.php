<?php

namespace App\Http\Resources\Table;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use LibreNMS\Util\Mac;
use LibreNMS\Util\Time;

/**
 * Port NAC session, the status columns are icons or labels
 *
 * @mixin \App\Models\PortsNac
 */
class NacResource extends JsonResource
{
    private const string SUCCESS = '<i class="fa fa-check-circle fa-lg" aria-hidden="true" style="color:green"></i>';
    private const string FAILED = '<i class="fa fa-times-circle fa-lg" aria-hidden="true" style="color:red"></i>';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mac = Mac::parse($this->mac_address);

        return [
            'id' => $this->ports_nac_id,
            'device_id' => Blade::render('<x-device-link :device="$device"/>', ['device' => $this->device]),
            'port_id' => Blade::render('<x-port-link :port="$port">{{ $port->getShortLabel() }}</x-port-link>', ['port' => $this->port]),
            'mac_address' => $mac->readable(),
            'mac_oui' => $mac->vendor(),
            'ip_address' => $this->ip_address,
            'vlan' => $this->vlan,
            'domain' => match ($this->domain) {
                'voice' => '<i class="fa fa-phone fa-lg" aria-hidden="true" title="voice"></i>',
                'data', 'Disabled' => '<i class="fa fa-desktop fa-lg" aria-hidden="true" title="' . e($this->domain) . '"></i>',
                default => $this->label($this->domain),
            },
            'host_mode' => $this->host_mode,
            'username' => $this->username,
            'authz_by' => $this->authz_by,
            'timeout' => Time::formatInterval($this->timeout, parts: 2),
            'time_elapsed' => Time::formatInterval($this->time_elapsed, parts: 2),
            'time_left' => Time::formatInterval($this->time_left, parts: 2),
            'authc_status' => match ($this->authc_status) {
                'notRun', 'running' => $this->label($this->authc_status, 'label-primary'),
                'authcSuccess' => self::SUCCESS,
                'failedOver', 'authcFailed', '6' => self::FAILED,
                default => $this->label($this->authc_status),
            },
            // sussess is a typo in the Huawei MIB
            'authz_status' => match ($this->authz_status) {
                'authorizationSuccess', 'sussess' => self::SUCCESS,
                'authorizationFailed' => self::FAILED,
                default => $this->label($this->authz_status),
            },
            'method' => match ($this->method) {
                'dot1x' => $this->label('802.1x', 'label-success'),
                'macAuthBypass' => $this->label('MAB', 'label-primary'),
                'other' => $this->label('Disabled', 'label-danger'),
                default => $this->label($this->method),
            },
            'created_at' => $this->created_at?->toDateTimeString() ?? '',
            'updated_at' => $this->updated_at ? ($this->historical ? $this->updated_at->toDateTimeString() : $this->updated_at->diffForHumans()) : '',
        ];
    }

    private function label(?string $text, string $class = 'label-default'): string
    {
        return '<span class="label ' . $class . '" title="' . e($text) . '">' . e($text) . '</span>';
    }
}
