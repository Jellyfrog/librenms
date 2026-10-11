<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int|null $mac_count
 */
class PortsFdb extends PortRelatedModel
{
    use HasFactory;
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
     * ARP entries are learned on the L3 device, which is usually not the device holding the fdb entry,
     * so this can only be matched on mac address. The same mac/ip pair is often seen by several devices,
     * ports or contexts, only return it once.
     * The returned models are partial and only contain mac_address and ipv4_address.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Ipv4Mac, $this>
     */
    public function ipv4Addresses(): HasMany
    {
        return $this->hasMany(Ipv4Mac::class, 'mac_address', 'mac_address')
            ->select(['ipv4_mac.mac_address', 'ipv4_mac.ipv4_address'])
            ->distinct();
    }

    /**
     * Matched on mac address only, see ipv4Addresses()
     * The returned models are partial and only contain mac_address and ipv6_address.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Ipv6Nd, $this>
     */
    public function ipv6Addresses(): HasMany
    {
        return $this->hasMany(Ipv6Nd::class, 'mac_address', 'mac_address')
            ->select(['ipv6_nd.mac_address', 'ipv6_nd.ipv6_address'])
            ->distinct();
    }
}
