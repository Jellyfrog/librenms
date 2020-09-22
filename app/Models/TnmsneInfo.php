<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TnmsneInfo
 *
 * @property int $id
 * @property int $device_id
 * @property int $neID
 * @property string $neType
 * @property string $neName
 * @property string $neLocation
 * @property string $neAlarm
 * @property string $neOpMode
 * @property string $neOpState
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo whereNeAlarm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo whereNeID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo whereNeLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo whereNeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo whereNeOpMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo whereNeOpState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\TnmsneInfo whereNeType($value)
 * @mixin \Eloquent
 */
class TnmsneInfo extends Model
{
    protected $table = 'tnmsneinfo';
    public $timestamps = false;
    protected $fillable = [
        'device_id',
        'neID',
        'neType',
        'neName',
        'neLocation',
        'neAlarm',
        'neOpMode',
        'neOpState',
    ];
}
