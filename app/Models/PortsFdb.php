<?php

namespace App\Models;

/**
 * App\Models\PortsFdb
 *
 * @property int $ports_fdb_id
 * @property int $port_id
 * @property string $mac_address
 * @property int $vlan_id
 * @property int $device_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Device $device
 * @property-read \App\Models\Port $port
 * @property-read \App\Models\Vlan $vlan
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb whereMacAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb wherePortId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb wherePortsFdbId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsFdb whereVlanId($value)
 * @mixin \Eloquent
 */
class PortsFdb extends PortRelatedModel
{
    protected $table = 'ports_fdb';
    protected $primaryKey = 'ports_fdb_id';
    public $timestamps = true;

    // ---- Define Relationships ----

    public function device()
    {
        return $this->belongsTo(\App\Models\Device::class, 'device_id', 'device_id');
    }

    public function vlan()
    {
        return $this->belongsTo(\App\Models\Vlan::class, 'vlan_id', 'vlan_id');
    }
}
