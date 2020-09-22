<?php

namespace App\Models;

/**
 * App\Models\PortStatistic
 *
 * @property int $port_id
 * @property int|null $ifInNUcastPkts
 * @property int|null $ifInNUcastPkts_prev
 * @property int|null $ifInNUcastPkts_delta
 * @property int|null $ifInNUcastPkts_rate
 * @property int|null $ifOutNUcastPkts
 * @property int|null $ifOutNUcastPkts_prev
 * @property int|null $ifOutNUcastPkts_delta
 * @property int|null $ifOutNUcastPkts_rate
 * @property int|null $ifInDiscards
 * @property int|null $ifInDiscards_prev
 * @property int|null $ifInDiscards_delta
 * @property int|null $ifInDiscards_rate
 * @property int|null $ifOutDiscards
 * @property int|null $ifOutDiscards_prev
 * @property int|null $ifOutDiscards_delta
 * @property int|null $ifOutDiscards_rate
 * @property int|null $ifInUnknownProtos
 * @property int|null $ifInUnknownProtos_prev
 * @property int|null $ifInUnknownProtos_delta
 * @property int|null $ifInUnknownProtos_rate
 * @property int|null $ifInBroadcastPkts
 * @property int|null $ifInBroadcastPkts_prev
 * @property int|null $ifInBroadcastPkts_delta
 * @property int|null $ifInBroadcastPkts_rate
 * @property int|null $ifOutBroadcastPkts
 * @property int|null $ifOutBroadcastPkts_prev
 * @property int|null $ifOutBroadcastPkts_delta
 * @property int|null $ifOutBroadcastPkts_rate
 * @property int|null $ifInMulticastPkts
 * @property int|null $ifInMulticastPkts_prev
 * @property int|null $ifInMulticastPkts_delta
 * @property int|null $ifInMulticastPkts_rate
 * @property int|null $ifOutMulticastPkts
 * @property int|null $ifOutMulticastPkts_prev
 * @property int|null $ifOutMulticastPkts_delta
 * @property int|null $ifOutMulticastPkts_rate
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInBroadcastPkts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInBroadcastPktsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInBroadcastPktsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInBroadcastPktsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInDiscards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInDiscardsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInDiscardsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInDiscardsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInMulticastPkts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInMulticastPktsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInMulticastPktsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInMulticastPktsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInNUcastPkts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInNUcastPktsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInNUcastPktsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInNUcastPktsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInUnknownProtos($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInUnknownProtosDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInUnknownProtosPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfInUnknownProtosRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutBroadcastPkts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutBroadcastPktsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutBroadcastPktsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutBroadcastPktsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutDiscards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutDiscardsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutDiscardsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutDiscardsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutMulticastPkts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutMulticastPktsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutMulticastPktsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutMulticastPktsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutNUcastPkts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutNUcastPktsDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutNUcastPktsPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic whereIfOutNUcastPktsRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStatistic wherePortId($value)
 * @mixin \Eloquent
 */
class PortStatistic extends PortRelatedModel
{
    protected $table = 'ports_statistics';
    protected $primaryKey = 'port_id';
    public $timestamps = false;
}
