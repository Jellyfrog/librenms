<?php

namespace App\Models;

/**
 * App\Models\HrDevice
 *
 * @property int $hrDevice_id
 * @property int $device_id
 * @property int $hrDeviceIndex
 * @property string $hrDeviceDescr
 * @property string $hrDeviceType
 * @property int $hrDeviceErrors
 * @property string $hrDeviceStatus
 * @property int|null $hrProcessorLoad
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice whereHrDeviceDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice whereHrDeviceErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice whereHrDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice whereHrDeviceIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice whereHrDeviceStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice whereHrDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\HrDevice whereHrProcessorLoad($value)
 * @mixin \Eloquent
 */
class HrDevice extends DeviceRelatedModel
{
    protected $table = 'hrDevice';

    protected $primaryKey = 'hrDevice_id';
}
