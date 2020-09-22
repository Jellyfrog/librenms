<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Stp
 *
 * @property int $stp_id
 * @property int $device_id
 * @property int $rootBridge
 * @property string $bridgeAddress
 * @property string $protocolSpecification
 * @property int $priority
 * @property string $timeSinceTopologyChange
 * @property int $topChanges
 * @property string $designatedRoot
 * @property int $rootCost
 * @property int|null $rootPort
 * @property int $maxAge
 * @property int $helloTime
 * @property int $holdTime
 * @property int $forwardDelay
 * @property int $bridgeMaxAge
 * @property int $bridgeHelloTime
 * @property int $bridgeForwardDelay
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereBridgeAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereBridgeForwardDelay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereBridgeHelloTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereBridgeMaxAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereDesignatedRoot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereForwardDelay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereHelloTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereHoldTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereMaxAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereProtocolSpecification($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereRootBridge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereRootCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereRootPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereStpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereTimeSinceTopologyChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Stp whereTopChanges($value)
 * @mixin \Eloquent
 */
class Stp extends Model
{
    protected $table = 'stp';
    protected $primaryKey = 'stp_id';
    public $timestamps = false;
}
