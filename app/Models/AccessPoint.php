<?php

namespace App\Models;

/**
 * App\Models\AccessPoint
 *
 * @property int $accesspoint_id
 * @property int $device_id
 * @property string $name
 * @property int|null $radio_number
 * @property string $type
 * @property string $mac_addr
 * @property int $deleted
 * @property int $channel
 * @property int $txpow
 * @property int $radioutil
 * @property int $numasoclients
 * @property int $nummonclients
 * @property int $numactbssid
 * @property int $nummonbssid
 * @property int $interference
 * @property-read \App\Models\Device $device
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereAccesspointId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereInterference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereMacAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereNumactbssid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereNumasoclients($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereNummonbssid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereNummonclients($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereRadioNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereRadioutil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereTxpow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AccessPoint whereType($value)
 * @mixin \Eloquent
 */
class AccessPoint extends DeviceRelatedModel
{
    protected $primaryKey = 'accesspoint_id';
    public $timestamps = false;
}
