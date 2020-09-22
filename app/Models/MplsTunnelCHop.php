<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LibreNMS\Interfaces\Models\Keyable;

/**
 * App\Models\MplsTunnelCHop
 *
 * @property int $c_hop_id
 * @property int $mplsTunnelCHopListIndex
 * @property int $mplsTunnelCHopIndex
 * @property int $device_id
 * @property int|null $lsp_path_id
 * @property string|null $mplsTunnelCHopAddrType
 * @property string|null $mplsTunnelCHopIpv4Addr
 * @property string|null $mplsTunnelCHopIpv6Addr
 * @property int|null $mplsTunnelCHopAsNumber
 * @property string|null $mplsTunnelCHopStrictOrLoose
 * @property string|null $mplsTunnelCHopRouterId
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereCHopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereLspPathId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereMplsTunnelCHopAddrType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereMplsTunnelCHopAsNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereMplsTunnelCHopIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereMplsTunnelCHopIpv4Addr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereMplsTunnelCHopIpv6Addr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereMplsTunnelCHopListIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereMplsTunnelCHopRouterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelCHop whereMplsTunnelCHopStrictOrLoose($value)
 * @mixin \Eloquent
 */
class MplsTunnelCHop extends Model implements Keyable
{
    protected $primaryKey = 'c_hop_id';
    public $timestamps = false;
    protected $fillable = [
        'c_hop_id',
        'mplsTunnelCHopListIndex',
        'mplsTunnelCHopIndex',
        'lsp_path_id',
        'device_id',
        'mplsTunnelCHopAddrType',
        'mplsTunnelCHopIpv4Addr',
        'mplsTunnelCHopIpv6Addr',
        'mplsTunnelCHopAsNumber',
        'mplsTunnelCHopStrictOrLoose',
        'mplsTunnelCHopRouterId',
    ];

    // ---- Helper Functions ----

    /**
     * Get a string that can identify a unique instance of this model
     * @return string
     */
    public function getCompositeKey()
    {
        return $this->mplsTunnelCHopListIndex . '-' . $this->mplsTunnelCHopIndex;
    }
}
