<?php

namespace App\Models;

/**
 * App\Models\Ipv4Mac
 *
 * @property int $port_id
 * @property int|null $device_id
 * @property string $mac_address
 * @property string $ipv4_address
 * @property string $context_name
 * @property-read \App\Models\Device|null $device
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Mac newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Mac newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Mac query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Mac whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Mac whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Mac whereIpv4Address($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Mac whereMacAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Mac wherePortId($value)
 * @mixin \Eloquent
 */
class Ipv4Mac extends PortRelatedModel
{
    protected $table = 'ipv4_mac';
    public $timestamps = false;

    // ---- Define Relationships ----

    public function device()
    {
        return $this->belongsTo(\App\Models\Device::class, 'device_id');
    }
}
