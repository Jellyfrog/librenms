<?php

namespace App\Models;

/**
 * App\Models\PortVlan
 *
 * @property int $port_vlan_id
 * @property int $device_id
 * @property int $port_id
 * @property int $vlan
 * @property int $baseport
 * @property int $priority
 * @property string $state
 * @property int $cost
 * @property int $untagged
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan whereBaseport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan wherePortId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan wherePortVlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan whereUntagged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortVlan whereVlan($value)
 * @mixin \Eloquent
 */
class PortVlan extends PortRelatedModel
{
    protected $table = 'ports_vlans';
    protected $primaryKey = 'port_vlan_id';
    public $timestamps = false;
}
