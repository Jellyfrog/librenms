<?php

namespace App\Models;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LibreNMS\Interfaces\Models\Keyable;

#[ApiResource(
    shortName: 'PortSecurity',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'device_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'port_id', filter: new EqualsFilter())]
class PortSecurity extends DeviceRelatedModel implements Keyable
{
    use HasFactory;

    protected $table = 'port_security';
    // protected $primaryKey = 'port_id';
    public $timestamps = false;
    protected $fillable = [
        'port_id',
        'device_id',
        'port_security_enable',
        'status',
        'max_addresses',
        'address_count',
        'violation_action',
        'violation_count',
        'last_mac_address',
        'sticky_enable',
    ];

    public function getCompositeKey(): int
    {
        return (int) $this->port_id;
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
