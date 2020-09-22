<?php

namespace App\Models;

/**
 * App\Models\Sla
 *
 * @property int $sla_id
 * @property int $device_id
 * @property int $sla_nr
 * @property string $owner
 * @property string $tag
 * @property string $rtt_type
 * @property int $status
 * @property int $opstatus
 * @property int $deleted
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla whereOpstatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla whereOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla whereRttType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla whereSlaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla whereSlaNr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sla whereTag($value)
 * @mixin \Eloquent
 */
class Sla extends DeviceRelatedModel
{
    protected $primaryKey = 'sla_id';
    public $timestamps = false;
}
