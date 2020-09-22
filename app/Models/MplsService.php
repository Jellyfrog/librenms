<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LibreNMS\Interfaces\Models\Keyable;

/**
 * App\Models\MplsService
 *
 * @property int $svc_id
 * @property int $svc_oid
 * @property int $device_id
 * @property string|null $svcRowStatus
 * @property string|null $svcType
 * @property int|null $svcCustId
 * @property string|null $svcAdminStatus
 * @property string|null $svcOperStatus
 * @property string|null $svcDescription
 * @property int|null $svcMtu
 * @property int|null $svcNumSaps
 * @property int|null $svcNumSdps
 * @property int|null $svcLastMgmtChange
 * @property int|null $svcLastStatusChange
 * @property int|null $svcVRouterId
 * @property string|null $svcTlsMacLearning
 * @property string|null $svcTlsStpAdminStatus
 * @property string|null $svcTlsStpOperStatus
 * @property int|null $svcTlsFdbTableSize
 * @property int|null $svcTlsFdbNumEntries
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsSdpBind[] $binds
 * @property-read int|null $binds_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcAdminStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcCustId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcLastMgmtChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcLastStatusChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcMtu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcNumSaps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcNumSdps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcOperStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcRowStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcTlsFdbNumEntries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcTlsFdbTableSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcTlsMacLearning($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcTlsStpAdminStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcTlsStpOperStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsService whereSvcVRouterId($value)
 * @mixin \Eloquent
 */
class MplsService extends Model implements Keyable
{
    protected $primaryKey = 'svc_id';
    public $timestamps = false;
    protected $fillable = [
        'svc_oid',
        'device_id',
        'svcRowStatus',
        'svcType',
        'svcCustId',
        'svcAdminStatus',
        'svcOperStatus',
        'svcDescription',
        'svcMtu',
        'svcNumSaps',
        'svcNumSdps',
        'svcLastMgmtChange',
        'svcLastStatusChange',
        'svcVRouterId',
        'svcTlsMacLearning',
        'svcTlsStpAdminStatus',
        'svcTlsStpOperStatus',
        'svcTlsFdbTableSize',
        'svcTlsFdbNumEntries',
    ];

    // ---- Helper Functions ----

    /**
     * Get a string that can identify a unique instance of this model
     * @return string
     */
    public function getCompositeKey()
    {
        return $this->svc_oid;
    }

    // ---- Define Relationships ----

    public function binds()
    {
        return $this->hasMany(\App\Models\MplsSdpBind::class, 'svc_id');
    }
}
