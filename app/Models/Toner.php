<?php

namespace App\Models;

/**
 * App\Models\Toner
 *
 * @property int $toner_id
 * @property int $device_id
 * @property int $toner_index
 * @property string $toner_type
 * @property string $toner_oid
 * @property string $toner_descr
 * @property int $toner_capacity
 * @property int $toner_current
 * @property string|null $toner_capacity_oid
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner whereTonerCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner whereTonerCapacityOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner whereTonerCurrent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner whereTonerDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner whereTonerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner whereTonerIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner whereTonerOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Toner whereTonerType($value)
 * @mixin \Eloquent
 */
class Toner extends DeviceRelatedModel
{
    protected $table = 'toner';
    protected $primaryKey = 'toner_id';
    public $timestamps = false;
}
