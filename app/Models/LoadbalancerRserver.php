<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\LoadbalancerRserver
 *
 * @property int $rserver_id
 * @property string $farm_id
 * @property int $device_id
 * @property string $StateDescr
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerRserver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerRserver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerRserver query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerRserver whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerRserver whereFarmId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerRserver whereRserverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerRserver whereStateDescr($value)
 * @mixin \Eloquent
 */
class LoadbalancerRserver extends Model
{
    protected $table = 'loadbalancer_rservers';
    protected $primaryKey = 'rserver_id';
    public $timestamps = false;
}
