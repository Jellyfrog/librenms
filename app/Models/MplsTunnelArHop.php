<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LibreNMS\Interfaces\Models\Keyable;

/**
 * App\Models\MplsTunnelArHop
 *
 * @property int $ar_hop_id
 * @property int $mplsTunnelARHopListIndex
 * @property int $mplsTunnelARHopIndex
 * @property int $device_id
 * @property int $lsp_path_id
 * @property string|null $mplsTunnelARHopAddrType
 * @property string|null $mplsTunnelARHopIpv4Addr
 * @property string|null $mplsTunnelARHopIpv6Addr
 * @property int|null $mplsTunnelARHopAsNumber
 * @property string|null $mplsTunnelARHopStrictOrLoose
 * @property string|null $mplsTunnelARHopRouterId
 * @property string $localProtected
 * @property string $linkProtectionInUse
 * @property string $bandwidthProtected
 * @property string $nextNodeProtected
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereArHopId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereBandwidthProtected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereLinkProtectionInUse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereLocalProtected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereLspPathId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereMplsTunnelARHopAddrType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereMplsTunnelARHopAsNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereMplsTunnelARHopIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereMplsTunnelARHopIpv4Addr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereMplsTunnelARHopIpv6Addr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereMplsTunnelARHopListIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereMplsTunnelARHopRouterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereMplsTunnelARHopStrictOrLoose($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MplsTunnelArHop whereNextNodeProtected($value)
 * @mixin \Eloquent
 */
class MplsTunnelArHop extends Model implements Keyable
{
    protected $primaryKey = 'ar_hop_id';
    public $timestamps = false;
    protected $fillable = [
        'ar_hop_id',
        'mplsTunnelARHopListIndex',
        'mplsTunnelARHopIndex',
        'lsp_path_id',
        'device_id',
        'mplsTunnelARHopAddrType',
        'mplsTunnelARHopIpv4Addr',
        'mplsTunnelARHopIpv6Addr',
        'mplsTunnelARHopAsNumber',
        'mplsTunnelARHopStrictOrLoose',
        'mplsTunnelARHopRouterId',
        'localProtected',
        'linkProtectionInUse',
        'bandwidthProtected',
        'nextNodeProtected',
    ];

    // ---- Helper Functions ----

    /**
     * Get a string that can identify a unique instance of this model
     * @return string
     */
    public function getCompositeKey()
    {
        return $this->mplsTunnelARHopListIndex . '-' . $this->mplsTunnelARHopIndex;
    }
}
