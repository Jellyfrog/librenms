<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Models\Device;
use App\Models\Port;
use App\Models\Sensor;
use Illuminate\Support\Facades\Auth;
use LibreNMS\Util\Version;

/**
 * @implements ProviderInterface<System>
 */
class SystemProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): System
    {
        $user = Auth::user();

        $system = new System;
        $system->version = Version::VERSION;
        $system->device_count = Device::query()->hasAccess($user)->count();
        $system->port_count = Port::query()->hasAccess($user)->count();
        $system->sensor_count = Sensor::query()->hasAccess($user)->count();

        return $system;
    }
}
