<?php

namespace App\Models;

/**
 * App\Models\PortAdsl
 *
 * @property int $port_id
 * @property string $port_adsl_updated
 * @property string $adslLineCoding
 * @property string $adslLineType
 * @property string $adslAtucInvVendorID
 * @property string $adslAtucInvVersionNumber
 * @property float $adslAtucCurrSnrMgn
 * @property float $adslAtucCurrAtn
 * @property float $adslAtucCurrOutputPwr
 * @property int $adslAtucCurrAttainableRate
 * @property int $adslAtucChanCurrTxRate
 * @property string $adslAturInvSerialNumber
 * @property string $adslAturInvVendorID
 * @property string $adslAturInvVersionNumber
 * @property int $adslAturChanCurrTxRate
 * @property float $adslAturCurrSnrMgn
 * @property float $adslAturCurrAtn
 * @property float $adslAturCurrOutputPwr
 * @property int $adslAturCurrAttainableRate
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAtucChanCurrTxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAtucCurrAtn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAtucCurrAttainableRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAtucCurrOutputPwr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAtucCurrSnrMgn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAtucInvVendorID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAtucInvVersionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAturChanCurrTxRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAturCurrAtn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAturCurrAttainableRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAturCurrOutputPwr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAturCurrSnrMgn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAturInvSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAturInvVendorID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslAturInvVersionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslLineCoding($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl whereAdslLineType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl wherePortAdslUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortAdsl wherePortId($value)
 * @mixin \Eloquent
 */
class PortAdsl extends PortRelatedModel
{
    protected $table = 'ports_adsl';
    protected $primaryKey = 'port_id';
    public $timestamps = false;
}
