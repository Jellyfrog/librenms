<?php

namespace App\Models;

/**
 * App\Models\Vminfo
 *
 * @property int $id
 * @property int $device_id
 * @property string $vm_type
 * @property int $vmwVmVMID
 * @property string $vmwVmDisplayName
 * @property string $vmwVmGuestOS
 * @property int $vmwVmMemSize
 * @property int $vmwVmCpus
 * @property string $vmwVmState
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo whereVmType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo whereVmwVmCpus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo whereVmwVmDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo whereVmwVmGuestOS($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo whereVmwVmMemSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo whereVmwVmState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vminfo whereVmwVmVMID($value)
 * @mixin \Eloquent
 */
class Vminfo extends DeviceRelatedModel
{
    protected $table = 'vminfo';
    public $timestamps = false;
}
