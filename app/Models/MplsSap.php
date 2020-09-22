<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LibreNMS\Interfaces\Models\Keyable;

/**
 * App\Models\MplsSap
 *
 * @property int $sap_id
 * @property int $svc_id
 * @property int $svc_oid
 * @property int $sapPortId
 * @property string|null $ifName
 * @property int $device_id
 * @property string|null $sapEncapValue
 * @property string|null $sapRowStatus
 * @property string|null $sapType
 * @property string|null $sapDescription
 * @property string|null $sapAdminStatus
 * @property string|null $sapOperStatus
 * @property int|null $sapLastMgmtChange
 * @property int|null $sapLastStatusChange
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsSdpBind[] $binds
 * @property-read int|null $binds_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsService[] $services
 * @property-read int|null $services_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereIfName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapAdminStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapEncapValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapLastMgmtChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapLastStatusChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapOperStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapPortId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapRowStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSapType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSvcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsSap whereSvcOid($value)
 * @mixin \Eloquent
 */
class MplsSap extends Model implements Keyable
{
    protected $primaryKey = 'sap_id';
    public $timestamps = false;
    protected $fillable = [
        'svc_id',
        'svc_oid',
        'sapPortId',
        'ifName',
        'sapEncapValue',
        'device_id',
        'sapRowStatus',
        'sapType',
        'sapDescription',
        'sapAdminStatus',
        'sapOperStatus',
        'sapLastMgmtChange',
        'sapLastStatusChange',
    ];

    // ---- Helper Functions ----

    /**
     * Get a string that can identify a unique instance of this model
     * @return string
     */
    public function getCompositeKey()
    {
        return $this->svc_oid . '-' . $this->sapPortId . '-' . $this->sapEncapValue;
    }

    // ---- Define Relationships ----

    public function binds()
    {
        return $this->hasMany(\App\Models\MplsSdpBind::class, 'svc_id');
    }

    public function services()
    {
        return $this->hasMany(\App\Models\MplsService::class, 'svc_id');
    }
}
