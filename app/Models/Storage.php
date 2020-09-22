<?php

namespace App\Models;

/**
 * App\Models\Storage
 *
 * @property int $storage_id
 * @property int $device_id
 * @property string $storage_mib
 * @property string|null $storage_index
 * @property string|null $storage_type
 * @property string $storage_descr
 * @property int $storage_size
 * @property int $storage_units
 * @property int $storage_used
 * @property int $storage_free
 * @property int $storage_perc
 * @property int|null $storage_perc_warn
 * @property int $storage_deleted
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageFree($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageMib($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStoragePerc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStoragePercWarn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageUnits($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Storage whereStorageUsed($value)
 * @mixin \Eloquent
 */
class Storage extends DeviceRelatedModel
{
    protected $table = 'storage';
    protected $primaryKey = 'storage_id';
}
