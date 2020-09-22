<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LibreNMS\Interfaces\Models\Keyable;

/**
 * App\Models\MplsSdpBind
 *
 * @property int $bind_id
 * @property int $sdp_id
 * @property int $svc_id
 * @property int $sdp_oid
 * @property int $svc_oid
 * @property int $device_id
 * @property string|null $sdpBindRowStatus
 * @property string|null $sdpBindAdminStatus
 * @property string|null $sdpBindOperStatus
 * @property int|null $sdpBindLastMgmtChange
 * @property int|null $sdpBindLastStatusChange
 * @property string|null $sdpBindType
 * @property string|null $sdpBindVcType
 * @property int|null $sdpBindBaseStatsIngFwdPackets
 * @property int|null $sdpBindBaseStatsIngFwdOctets
 * @property int|null $sdpBindBaseStatsEgrFwdPackets
 * @property int|null $sdpBindBaseStatsEgrFwdOctets
 * @property-read \App\Models\MplsSdp $sdp
 * @property-read \App\Models\MplsService $service
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereBindId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindAdminStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindBaseStatsEgrFwdOctets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindBaseStatsEgrFwdPackets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindBaseStatsIngFwdOctets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindBaseStatsIngFwdPackets($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindLastMgmtChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindLastStatusChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindOperStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindRowStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpBindVcType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSdpOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSvcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSdpBind whereSvcOid($value)
 * @mixin \Eloquent
 */
class MplsSdpBind extends Model implements Keyable
{
    protected $primaryKey = 'bind_id';
    public $timestamps = false;
    protected $fillable = [
        'sdp_id',
        'svc_id',
        'sdp_oid',
        'svc_oid',
        'device_id',
        'sdpBindRowStatus',
        'sdpBindAdminStatus',
        'sdpBindOperStatus',
        'sdpBindLastMgmtChange',
        'sdpBindLastStatusChange',
        'sdpBindType',
        'sdpBindVcType',
        'sdpBindBaseStatsIngFwdPackets',
        'sdpBindBaseStatsIngFwdOctets',
        'sdpBindBaseStatsEgrFwdPackets',
        'sdpBindBaseStatsEgrFwdOctets',
    ];

    // ---- Helper Functions ----

    /**
     * Get a string that can identify a unique instance of this model
     * @return string
     */
    public function getCompositeKey()
    {
        return $this->sdp_oid . '-' . $this->svc_oid;
    }

    // ---- Define Relationships ----

    public function sdp()
    {
        return $this->belongsTo(\App\Models\MplsSdp::class, 'sdp_id');
    }

    public function service()
    {
        return $this->belongsTo(\App\Models\MplsService::class, 'svc_id');
    }
}
