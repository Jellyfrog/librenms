<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LibreNMS\Interfaces\Models\Keyable;

class Vlan extends DeviceRelatedModel implements Keyable
{
    use HasFactory;
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

    // ---- Define Relationships ----

    /**
     * ports_vlans has no vlan_id, it is matched on vlan number within the same device.
     * Works for lazy loading and has/count queries, but can not be eager loaded.
     *
     * @return HasMany<PortVlan, $this>
     */
    public function ports(): HasMany
    {
        $relation = $this->hasMany(PortVlan::class, 'vlan', 'vlan_vlan');

        return $this->device_id === null
            ? $relation->whereColumn('ports_vlans.device_id', 'vlans.device_id')
            : $relation->where('ports_vlans.device_id', $this->device_id);
    }

    /**
     * @return HasMany<PortsFdb, $this>
     */
    public function fdbEntries(): HasMany
    {
        return $this->hasMany(PortsFdb::class, 'vlan_id', 'vlan_id');
    }

    public function getCompositeKey(): string
    {
        return $this->vlan_vlan . '-' . $this->vlan_domain;
    }
}
