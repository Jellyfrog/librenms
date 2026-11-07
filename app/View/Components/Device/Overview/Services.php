<?php

namespace App\View\Components\Device\Overview;

use App\Models\Device;
use App\Models\Service;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use LibreNMS\Enum\ServiceStatus;

class Services extends Component
{
    /**
     * @var array{total: int, ok: int, warning: int, critical: int}
     */
    public array $counts;

    /** @var array<int, array{service: \App\Models\Service, status: string, name: string}> */
    public array $serviceLabels;

    public string $servicesUrl;

    public function __construct(public Device $device)
    {
        $this->counts = [
            'total' => $device->services->count(),
            'ok' => $device->services->where('service_status', ServiceStatus::Ok)->count(),
            'warning' => $device->services->where('service_status', ServiceStatus::Warning)->count(),
            'critical' => $device->services->where('service_status', ServiceStatus::Critical)->count(),
        ];
        $this->servicesUrl = route('device', ['device' => $device->device_id, 'tab' => 'services']);
        $this->serviceLabels = $device->services->map(function (Service $service): array {
            $type = strtolower((string) $service->service_type);

            return [
                'service' => $service,
                // there is no lnms-btn-info, show unknown as default
                'status' => $service->service_status === ServiceStatus::Unknown ? 'default' : $service->service_status->cssClass(),
                'name' => filled($service->service_name) && $service->service_name !== $type
                    ? "{$service->service_name} ($type)"
                    : $type,
            ];
        })->values()->all();
    }

    public function render(): View|Closure|string
    {
        return view('components.device.overview.services');
    }
}
