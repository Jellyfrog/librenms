<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LibreNMS\Interfaces\Models\Keyable;

/**
 * App\Models\MplsSdp
 *
 * @property int $sdp_id
 * @property int $sdp_oid
 * @property int $device_id
 * @property string|null $sdpRowStatus
 * @property string|null $sdpDelivery
 * @property string|null $sdpDescription
 * @property string|null $sdpAdminStatus
 * @property string|null $sdpOperStatus
 * @property int|null $sdpAdminPathMtu
 * @property int|null $sdpOperPathMtu
 * @property int|null $sdpLastMgmtChange
 * @property int|null $sdpLastStatusChange
 * @property string|null $sdpActiveLspType
 * @property string|null $sdpFarEndInetAddressType
 * @property string|null $sdpFarEndInetAddress
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsSdpBind[] $binds
 * @property-read int|null $binds_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpActiveLspType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpAdminPathMtu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpAdminStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpDelivery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpFarEndInetAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpFarEndInetAddressType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpLastMgmtChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpLastStatusChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpOperPathMtu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpOperStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdp whereSdpRowStatus($value)
 * @mixin \Eloquent
 */
class MplsSdp extends Model implements Keyable
{
    protected $primaryKey = 'sdp_id';
    public $timestamps = false;
    protected $fillable = [
        'sdp_oid',
        'device_id',
        'sdpRowStatus',
        'sdpDelivery',
        'sdpDescription',
        'sdpAdminStatus',
        'sdpOperStatus',
        'sdpAdminPathMtu',
        'sdpOperPathMtu',
        'sdpLastMgmtChange',
        'sdpLastStatusChange',
        'sdpActiveLspType',
        'sdpFarEndInetAddressType',
        'sdpFarEndInetAddress',
    ];

    // ---- Helper Functions ----

    /**
     * Get a string that can identify a unique instance of this model
     * @return string
     */
    public function getCompositeKey()
    {
        return $this->sdp_oid;
    }

    // ---- Define Relationships ----

    public function binds()
    {
        return $this->hasMany(\App\Models\MplsSdpBind::class, 'sdp_id');
    }
}
