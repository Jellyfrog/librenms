<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\LoadbalancerVserver
 *
 * @property int $classmap_id
 * @property string $classmap
 * @property string $serverstate
 * @property int $device_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerVserver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerVserver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerVserver query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerVserver whereClassmap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerVserver whereClassmapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerVserver whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LoadbalancerVserver whereServerstate($value)
 * @mixin \Eloquent
 */
class LoadbalancerVserver extends Model
{
    protected $table = 'loadbalancer_vservers';
    protected $primaryKey = 'vserver_id';
    public $timestamps = false;
}
