<?php

namespace App\Models;

/**
 * App\Models\Vlan
 *
 * @property int $vlan_id
 * @property int|null $device_id
 * @property int|null $vlan_vlan
 * @property int|null $vlan_domain
 * @property string|null $vlan_name
 * @property string|null $vlan_type
 * @property int|null $vlan_mtu
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan whereVlanDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan whereVlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan whereVlanMtu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan whereVlanName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan whereVlanType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vlan whereVlanVlan($value)
 * @mixin \Eloquent
 */
class Vlan extends DeviceRelatedModel
{
    public $timestamps = false;
}
