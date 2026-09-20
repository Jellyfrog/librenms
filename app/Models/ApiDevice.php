<?php

namespace App\Models;

use ApiPlatform\Laravel\Eloquent\Filter\BooleanFilter;
use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link as ApiLink;
use ApiPlatform\Metadata\QueryParameter;
use App\Policies\DevicePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Device as the v2 API exposes it. See doc/API/v2.md and
 * https://api-platform.com/docs/laravel/.
 *
 * api-platform derives a resource's fields from the table schema, so every
 * column would be serialized, SNMP credentials included. $visible is the
 * allow-list that stops that: a column not named there never leaves the
 * server, including one a later migration adds. It lives on this subclass
 * rather than on Device because $visible also drives toArray(), which feeds
 * the v0 API, the legacy $device array in PollDevice/DiscoverDevice and the
 * alert payload.
 *
 * Read only, so DeviceObserver is deliberately not re-declared here: PHP
 * attributes are not inherited, and nothing on this path writes.
 */
#[UsePolicy(DevicePolicy::class)]
#[ApiResource(
    shortName: 'Device',
    description: 'A monitored device.',
    operations: [
        new GetCollection(policy: 'viewAny'),
        new Get(
            uriTemplate: '/devices/{id}{._format}',
            uriVariables: ['id' => new ApiLink(fromClass: self::class, identifiers: ['device_id'])],
            policy: 'view',
        ),
    ],
)]
#[ApiProperty(property: 'device_id', identifier: true, serialize: new SerializedName('id'))]
#[ApiProperty(property: 'features', serialize: new SerializedName('feature'))]
#[ApiProperty(property: 'notes', serialize: new SerializedName('note'))]
#[QueryParameter(key: 'hostname', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'sysName', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'display', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'hardware', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'serial', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'version', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'os', filter: EqualsFilter::class)]
#[QueryParameter(key: 'type', filter: EqualsFilter::class)]
#[QueryParameter(key: 'location_id', filter: EqualsFilter::class)]
#[QueryParameter(key: 'poller_group', filter: EqualsFilter::class)]
#[QueryParameter(key: 'status', filter: BooleanFilter::class)]
#[QueryParameter(key: 'disabled', filter: BooleanFilter::class)]
#[QueryParameter(key: 'ignore', filter: BooleanFilter::class)]
#[QueryParameter(key: 'last_polled', filter: DateFilter::class)]
#[QueryParameter(key: 'order[id]', filter: OrderFilter::class, property: 'device_id')]
#[QueryParameter(key: 'order[:property]', filter: OrderFilter::class, properties: [
    'hostname', 'sysName', 'display', 'os', 'type', 'status', 'uptime',
    'inserted', 'last_polled', 'last_discovered', 'last_ping',
])]
class ApiDevice extends Device
{
    protected $table = 'devices';

    /** The v2 API field list. Anything not named here stays server-side. */
    protected $visible = [
        'device_id', 'hostname', 'sysName', 'display', 'ip', 'os',
        'type', 'hardware', 'version', 'features', 'serial', 'icon',
        'purpose', 'notes', 'location_id', 'sysDescr', 'sysContact',
        'sysObjectID', 'status', 'status_reason', 'ignore',
        'ignore_status', 'disabled', 'disable_notify', 'uptime',
        'poller_group', 'inserted', 'last_polled', 'last_discovered',
        'last_ping',
    ];
}
