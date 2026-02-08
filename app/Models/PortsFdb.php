<?php

namespace App\Models;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ApiResource(
    shortName: 'FdbEntry',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'device_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'port_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'mac_address', filter: new PartialSearchFilter())]
class PortsFdb extends PortRelatedModel
{
    protected $table = 'ports_fdb';
    protected $primaryKey = 'ports_fdb_id';
    public $timestamps = true;

    // ---- Define Relationships ----
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vlan, $this>
     */
    public function vlan(): BelongsTo
    {
        return $this->belongsTo(Vlan::class, 'vlan_id', 'vlan_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Ipv4Mac, $this>
     */
    public function ipv4Addresses(): HasMany
    {
        return $this->hasMany(Ipv4Mac::class, 'mac_address', 'mac_address');
    }
}
