<?php

namespace App\Http\Resources\Table;

use App\Facades\LibrenmsConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use LibreNMS\Enum\DeviceStatus;
use LibreNMS\Util\Rewrite;
use LibreNMS\Util\Time;
use LibreNMS\Util\Url;

/**
 * Device list row, format=list_basic leaves out the detail lines
 *
 * @mixin \App\Models\Device
 */
class DeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $detailed = $request->input('format', 'list_detail') === 'list_detail';
        $deviceStatus = $this->getDeviceStatus();

        return [
            'device_id' => $this->device_id,
            'status' => match ($deviceStatus) {
                DeviceStatus::Down, DeviceStatus::NeverPolled => 'down',
                DeviceStatus::IgnoredUp, DeviceStatus::Up => 'up',
                DeviceStatus::IgnoredDown, DeviceStatus::Disabled => 'disabled',
            },
            'extra' => $this->statusLabel(),
            'maintenance' => $this->isUnderMaintenance(),
            'icon' => asset($this->icon),
            'icon_title' => pathinfo((string) $this->icon, PATHINFO_FILENAME),
            'hostname' => Url::modernDeviceLink($this->resource, extra: $detailed ? $this->name() : ''),
            'metrics' => $this->metrics($detailed),
            'hardware' => Rewrite::ciscoHardware($this->resource),
            'os' => LibrenmsConfig::getOsSetting($this->os, 'text'),
            'version' => $detailed ? trim($this->version . ($this->features ? " ($this->features)" : '')) : null,
            'uptime' => $deviceStatus == DeviceStatus::NeverPolled
                ? __('device.never_polled')
                : Time::formatInterval($this->status ? $this->uptime : (int) $this->downSince()->diffInSeconds(null, true), true),
            'location' => Str::limit((string) $this->location, 32, ''),
            'actions' => view('device.actions', ['actions' => $this->actions($detailed)])->render(),
        ];
    }

    private function statusLabel(): string
    {
        if ($this->disabled || $this->disable_notify) {
            return 'blackbg';
        }

        if ($this->ignore) {
            return 'label-default';
        }

        if ($this->status == 0) {
            return 'label-danger';
        }

        $warningTime = LibrenmsConfig::get('uptime_warning', 86400);

        return $this->uptime < $warningTime && $this->uptime != 0 ? 'label-warning' : 'label-success';
    }

    /**
     * Links to the ports, health and wireless tabs with their counts (withCount)
     *
     * @return list<array{tab: string, url: string, icon: string, count: int}>
     */
    private function metrics(bool $detailed): array
    {
        return collect([
            ['tab' => 'ports', 'icon' => 'fa-link', 'count' => (int) $this->ports_count],
            ['tab' => 'health', 'icon' => 'fa-heartbeat', 'count' => (int) $this->sensors_count],
            ['tab' => 'wireless', 'icon' => 'fa-wifi', 'count' => (int) $this->wireless_sensors_count],
        ])->filter(fn ($metric) => $metric['count'] > 0)
            ->map(fn ($metric) => [...$metric, 'url' => Url::deviceUrl($this->resource, ['tab' => $metric['tab']])])
            ->values()
            ->all();
    }

    /**
     * Action icon rows, the detailed list uses two rows
     *
     * @return array<int, list<array<string, mixed>>>
     */
    private function actions(bool $detailed): array
    {
        $row = $detailed ? 1 : 0;
        $actions = [[
            ['title' => __('View Device'), 'href' => Url::deviceUrl($this->resource), 'icon' => 'fa-id-card', 'external' => false],
            ['title' => __('View alerts'), 'href' => route('device.faults', $this->device_id), 'icon' => 'fa-exclamation-circle', 'external' => false],
        ]];

        if (request()->user()?->can('device.update')) {
            $actions[0][] = ['title' => __('Edit device'), 'href' => route('device.edit', $this->device_id), 'icon' => 'fa-gear', 'external' => false];
        }

        $actions[$row][] = ['title' => 'Telnet to ' . $this->hostname, 'href' => 'telnet://' . $this->hostname, 'icon' => 'fa-terminal', 'external' => false];
        $actions[$row][] = ['title' => 'SSH to ' . $this->hostname, 'href' => $this->sshUrl(), 'icon' => 'fa-lock', 'external' => false];

        $httpPort = $this->getAttrib('override_device_http_port');
        $actions[$row][] = ['title' => 'Launch browser to ' . $this->hostname, 'href' => 'https://' . $this->hostname . ($httpPort ? ':' . $httpPort : ''), 'icon' => 'fa-globe'];

        foreach (array_values(Arr::wrap(LibrenmsConfig::get('html.device.links'))) as $index => $custom) {
            if ($custom['action'] ?? false) {
                $custom['href'] = Blade::render($custom['url'], ['device' => $this->resource]);
                $actions[$detailed ? $index % 2 : 0][] = $custom;
            }
        }

        return $actions;
    }

    private function sshUrl(): string
    {
        $server = LibrenmsConfig::get('gateone.server');
        if (! $server) {
            return 'ssh://' . $this->hostname;
        }

        $user = LibrenmsConfig::get('gateone.use_librenms_user') ? request()->user()?->username . '@' : '';

        return $server . '?ssh=ssh://' . $user . $this->hostname . '&location=' . $this->hostname;
    }
}
