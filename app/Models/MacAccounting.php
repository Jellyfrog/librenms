<?php

namespace App\Models;

/**
 * App\Models\MacAccounting
 *
 * @property int $ma_id
 * @property int $port_id
 * @property string $mac
 * @property string $in_oid
 * @property string $out_oid
 * @property int $bps_out
 * @property int $bps_in
 * @property int|null $cipMacHCSwitchedBytes_input
 * @property int|null $cipMacHCSwitchedBytes_input_prev
 * @property int|null $cipMacHCSwitchedBytes_input_delta
 * @property int|null $cipMacHCSwitchedBytes_input_rate
 * @property int|null $cipMacHCSwitchedBytes_output
 * @property int|null $cipMacHCSwitchedBytes_output_prev
 * @property int|null $cipMacHCSwitchedBytes_output_delta
 * @property int|null $cipMacHCSwitchedBytes_output_rate
 * @property int|null $cipMacHCSwitchedPkts_input
 * @property int|null $cipMacHCSwitchedPkts_input_prev
 * @property int|null $cipMacHCSwitchedPkts_input_delta
 * @property int|null $cipMacHCSwitchedPkts_input_rate
 * @property int|null $cipMacHCSwitchedPkts_output
 * @property int|null $cipMacHCSwitchedPkts_output_prev
 * @property int|null $cipMacHCSwitchedPkts_output_delta
 * @property int|null $cipMacHCSwitchedPkts_output_rate
 * @property int|null $poll_time
 * @property int|null $poll_prev
 * @property int|null $poll_period
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereBpsIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereBpsOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedBytesInput($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedBytesInputDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedBytesInputPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedBytesInputRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedBytesOutput($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedBytesOutputDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedBytesOutputPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedBytesOutputRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedPktsInput($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedPktsInputDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedPktsInputPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedPktsInputRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedPktsOutput($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedPktsOutputDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedPktsOutputPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereCipMacHCSwitchedPktsOutputRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereInOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereMaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereMac($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting whereOutOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting wherePollPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting wherePollPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting wherePollTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MacAccounting wherePortId($value)
 * @mixin \Eloquent
 */
class MacAccounting extends PortRelatedModel
{
    protected $table = 'mac_accounting';
    protected $primaryKey = 'ma_id';
    public $timestamps = false;
}
