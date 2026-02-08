<?php

namespace App\Models;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ApiResource(
    shortName: 'Vlan',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'device_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'vlan_vlan', filter: new EqualsFilter())]
#[QueryParameter(key: 'vlan_name', filter: new PartialSearchFilter())]
#[QueryParameter(key: 'order', filter: new OrderFilter())]
class Vlan extends DeviceRelatedModel
{
    protected $primaryKey = 'vlan_id';
    public $timestamps = false;
    protected $fillable = [
        'device_id',
        'vlan_vlan',
        'vlan_domain',
        'vlan_name',
        'vlan_type',
        'vlan_state',
    ];

    public function ports(): HasMany
    {
        return $this->hasMany(PortVlan::class, 'vlan', 'vlan_vlan')->where('ports_vlans.device_id', 'ports_vlans.device_id');
    }

    public function getCompositeKey(): string
    {
        return $this->vlan_vlan . '-' . $this->vlan_domain;
    }
}
