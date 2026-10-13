<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

/**
 * This LibreNMS install, and how much of it the current user may see.
 */
#[ApiResource(
    description: 'This LibreNMS install, and how much of it the current user may see.',
    operations: [
        new Get(uriTemplate: '/system{._format}', provider: SystemProvider::class),
    ],
)]
class System
{
    public string $name = 'LibreNMS';

    public string $version;

    /** Devices the user may access. */
    public int $device_count;

    /** Ports the user may access. */
    public int $port_count;

    /** Sensors the user may access. */
    public int $sensor_count;
}
