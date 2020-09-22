<?php

namespace App\Models;

/**
 * App\Models\NetscalerVserver
 *
 * @property int $vsvr_id
 * @property int $device_id
 * @property string $vsvr_name
 * @property string $vsvr_ip
 * @property int $vsvr_port
 * @property string $vsvr_type
 * @property string $vsvr_state
 * @property int $vsvr_clients
 * @property int $vsvr_server
 * @property int $vsvr_req_rate
 * @property int $vsvr_bps_in
 * @property int $vsvr_bps_out
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrBpsIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrBpsOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrClients($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrReqRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NetscalerVserver whereVsvrType($value)
 * @mixin \Eloquent
 */
class NetscalerVserver extends DeviceRelatedModel
{
    protected $primaryKey = 'vsvr_id';
    public $timestamps = false;
}
