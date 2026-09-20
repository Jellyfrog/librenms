<?php

namespace App\Models;

use ApiPlatform\Laravel\Eloquent\Filter\BooleanFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link as ApiLink;
use ApiPlatform\Metadata\QueryParameter;
use App\Policies\PortPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Port as the v2 API exposes it. $visible is the allow-list, see ApiDevice
 * for why it lives on a subclass. The per-poll counter bookkeeping (_prev,
 * _delta) is left out; the rates are what an API client wants.
 *
 * The link back to the device is device_id plus the /devices/{device_id}/ports
 * operation above; the device relation itself is not serialized, see the doc.
 */
#[UsePolicy(PortPolicy::class)]
#[ApiResource(
    shortName: 'Port',
    description: 'An interface on a monitored device.',
    operations: [
        new GetCollection(policy: 'viewAny'),
        new Get(
            uriTemplate: '/ports/{id}{._format}',
            uriVariables: ['id' => new ApiLink(fromClass: self::class, identifiers: ['port_id'])],
            policy: 'view',
        ),
        new GetCollection(
            uriTemplate: '/devices/{device_id}/ports{._format}',
            uriVariables: ['device_id' => new ApiLink(fromClass: ApiDevice::class, toProperty: 'device')],
            policy: 'viewAny',
        ),
    ],
)]
#[ApiProperty(property: 'port_id', identifier: true, serialize: new SerializedName('id'))]
#[ApiProperty(property: 'port_descr_type', serialize: new SerializedName('descr_type'))]
#[ApiProperty(property: 'port_descr_descr', serialize: new SerializedName('descr_descr'))]
#[ApiProperty(property: 'port_descr_circuit', serialize: new SerializedName('descr_circuit'))]
#[ApiProperty(property: 'port_descr_speed', serialize: new SerializedName('descr_speed'))]
#[ApiProperty(property: 'port_descr_notes', serialize: new SerializedName('descr_note'))]
#[QueryParameter(key: 'ifName', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'ifDescr', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'ifAlias', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'portName', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'device_id', filter: EqualsFilter::class)]
#[QueryParameter(key: 'ifIndex', filter: EqualsFilter::class)]
#[QueryParameter(key: 'ifType', filter: EqualsFilter::class)]
#[QueryParameter(key: 'ifOperStatus', filter: EqualsFilter::class)]
#[QueryParameter(key: 'ifAdminStatus', filter: EqualsFilter::class)]
#[QueryParameter(key: 'ifVlan', filter: EqualsFilter::class)]
#[QueryParameter(key: 'ignore', filter: BooleanFilter::class)]
#[QueryParameter(key: 'disabled', filter: BooleanFilter::class)]
#[QueryParameter(key: 'deleted', filter: BooleanFilter::class)]
// Sortable fields are an explicit allow-list, see the note on ApiDevice.
#[QueryParameter(key: 'order[id]', filter: OrderFilter::class, property: 'port_id')]
#[QueryParameter(key: 'order[:property]', filter: OrderFilter::class, properties: [
    'device_id', 'ifIndex', 'ifName', 'ifAlias', 'portName', 'ifType',
    'ifSpeed', 'ifOperStatus', 'ifAdminStatus', 'ifLastChange',
    'ifInOctets_rate', 'ifOutOctets_rate', 'poll_time',
])]
class ApiPort extends Port
{
    protected $table = 'ports';

    /** The v2 API field list. Anything not named here stays server-side. */
    protected $visible = [
        'port_id', 'device_id', 'ifIndex', 'ifName', 'ifDescr',
        'ifAlias', 'portName', 'ifType', 'ifSpeed', 'ifMtu',
        'ifDuplex', 'ifPhysAddress', 'ifConnectorPresent',
        'ifOperStatus', 'ifAdminStatus', 'ifLastChange', 'ifVlan',
        'ifTrunk', 'ifVrf', 'port_descr_type', 'port_descr_descr',
        'port_descr_circuit', 'port_descr_speed', 'port_descr_notes',
        'ignore', 'disabled', 'deleted', 'ifInOctets', 'ifOutOctets',
        'ifInOctets_rate', 'ifOutOctets_rate', 'ifInErrors_rate',
        'ifOutErrors_rate', 'poll_time',
    ];
}
