<?php

namespace App\Models;

/**
 * App\Models\DiskIo
 *
 * @property int $diskio_id
 * @property int $device_id
 * @property int $diskio_index
 * @property string $diskio_descr
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DiskIo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DiskIo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DiskIo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DiskIo whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DiskIo whereDiskioDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DiskIo whereDiskioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DiskIo whereDiskioIndex($value)
 * @mixin \Eloquent
 */
class DiskIo extends DeviceRelatedModel
{
    protected $table = 'ucd_diskio';

    protected $primaryKey = 'diskio_id';
}
