<?php

namespace App\Models;

/**
 * App\Models\DeviceGraph
 *
 * @property int $id
 * @property int $device_id
 * @property string|null $graph
 * @property-read \App\Models\Device $device
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceGraph newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceGraph newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceGraph query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceGraph whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceGraph whereGraph($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceGraph whereId($value)
 * @mixin \Eloquent
 */
class DeviceGraph extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $fillable = ['graph'];
}
