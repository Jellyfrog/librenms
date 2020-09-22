<?php

namespace App\Models;

/**
 * App\Models\MefInfo
 *
 * @property int $id
 * @property int $device_id
 * @property int $mefID
 * @property string $mefType
 * @property string $mefIdent
 * @property int $mefMTU
 * @property string $mefAdmState
 * @property string $mefRowState
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo whereMefAdmState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo whereMefID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo whereMefIdent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo whereMefMTU($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo whereMefRowState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MefInfo whereMefType($value)
 * @mixin \Eloquent
 */
class MefInfo extends DeviceRelatedModel
{
    protected $table = 'mefinfo';
    public $timestamps = false;
}
