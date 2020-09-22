<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LibreNMS\Interfaces\Models\Keyable;

/**
 * App\Models\MplsLsp
 *
 * @property int $lsp_id
 * @property int $vrf_oid
 * @property int $lsp_oid
 * @property int $device_id
 * @property string $mplsLspRowStatus
 * @property int|null $mplsLspLastChange
 * @property string $mplsLspName
 * @property string $mplsLspAdminState
 * @property string $mplsLspOperState
 * @property string $mplsLspFromAddr
 * @property string $mplsLspToAddr
 * @property string $mplsLspType
 * @property string $mplsLspFastReroute
 * @property int|null $mplsLspAge
 * @property int|null $mplsLspTimeUp
 * @property int|null $mplsLspTimeDown
 * @property int|null $mplsLspPrimaryTimeUp
 * @property int|null $mplsLspTransitions
 * @property int|null $mplsLspLastTransition
 * @property int|null $mplsLspConfiguredPaths
 * @property int|null $mplsLspStandbyPaths
 * @property int|null $mplsLspOperationalPaths
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\MplsLspPath[] $paths
 * @property-read int|null $paths_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereLspId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereLspOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspAdminState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspConfiguredPaths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspFastReroute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspFromAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspLastChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspLastTransition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspOperState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspOperationalPaths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspPrimaryTimeUp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspRowStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspStandbyPaths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspTimeDown($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspTimeUp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspToAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspTransitions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereMplsLspType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLsp whereVrfOid($value)
 * @mixin \Eloquent
 */
class MplsLsp extends Model implements Keyable
{
    protected $primaryKey = 'lsp_id';
    public $timestamps = false;
    protected $fillable = [
        'vrf_oid',
        'lsp_oid',
        'device_id',
        'mplsLspRowStatus',
        'mplsLspLastChange',
        'mplsLspName',
        'mplsLspAdminState',
        'mplsLspOperState',
        'mplsLspFromAddr',
        'mplsLspToAddr',
        'mplsLspType',
        'mplsLspFastReroute',
        'mplsLspAge',
        'mplsLspTimeUp',
        'mplsLspTimeDown',
        'mplsLspPrimaryTimeUp',
        'mplsLspTransitions',
        'mplsLspLastTransition',
        'mplsLspConfiguredPaths',
        'mplsLspStandbyPaths',
        'mplsLspOperationalPaths',
    ];

    // ---- Helper Functions ----

    /**
     * Get a string that can identify a unique instance of this model
     * @return string
     */
    public function getCompositeKey()
    {
        return $this->vrf_oid . '-' . $this->lsp_oid;
    }

    // ---- Define Relationships ----

    public function paths()
    {
        return $this->hasMany(\App\Models\MplsLspPath::class, 'lsp_id');
    }
}
