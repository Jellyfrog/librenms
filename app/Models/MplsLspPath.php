<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LibreNMS\Interfaces\Models\Keyable;

/**
 * App\Models\MplsLspPath
 *
 * @property int $lsp_path_id
 * @property int $lsp_id
 * @property int $path_oid
 * @property int $device_id
 * @property string $mplsLspPathRowStatus
 * @property int $mplsLspPathLastChange
 * @property string $mplsLspPathType
 * @property int $mplsLspPathBandwidth
 * @property int $mplsLspPathOperBandwidth
 * @property string $mplsLspPathAdminState
 * @property string $mplsLspPathOperState
 * @property string $mplsLspPathState
 * @property string $mplsLspPathFailCode
 * @property string $mplsLspPathFailNodeAddr
 * @property int $mplsLspPathMetric
 * @property int|null $mplsLspPathOperMetric
 * @property int|null $mplsLspPathTimeUp
 * @property int|null $mplsLspPathTimeDown
 * @property int|null $mplsLspPathTransitionCount
 * @property int|null $mplsLspPathTunnelARHopListIndex
 * @property int|null $mplsLspPathTunnelCHopListIndex
 * @property-read \App\Models\MplsLsp $lsp
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereLspId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereLspPathId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathAdminState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathBandwidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathFailCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathFailNodeAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathLastChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathMetric($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathOperBandwidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathOperMetric($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathOperState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathRowStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathTimeDown($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathTimeUp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathTransitionCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathTunnelARHopListIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathTunnelCHopListIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath whereMplsLspPathType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsLspPath wherePathOid($value)
 * @mixin \Eloquent
 */
class MplsLspPath extends Model implements Keyable
{
    protected $primaryKey = 'lsp_path_id';
    public $timestamps = false;
    protected $fillable = [
        'lsp_id',
        'path_oid',
        'device_id',
        'mplsLspPathRowStatus',
        'mplsLspPathLastChange',
        'mplsLspPathType',
        'mplsLspPathBandwidth',
        'mplsLspPathOperBandwidth',
        'mplsLspPathAdminState',
        'mplsLspPathOperState',
        'mplsLspPathState',
        'mplsLspPathFailCode',
        'mplsLspPathFailNodeAddr',
        'mplsLspPathMetric',
        'mplsLspPathOperMetric',
        'mplsLspPathTimeUp',
        'mplsLspPathTimeDown',
        'mplsLspPathTransitionCount',
        'mplsLspPathTunnelARHopListIndex',
        'mplsLspPathTunnelCHopListIndex',
    ];

    // ---- Helper Functions ----

    /**
     * Get a string that can identify a unique instance of this model
     * @return string
     */
    public function getCompositeKey()
    {
        return $this->lsp_id . '-' . $this->path_oid;
    }

    // ---- Define Relationships ----

    public function lsp()
    {
        return $this->belongsTo(\App\Models\MplsLsp::class, 'lsp_id');
    }
}
