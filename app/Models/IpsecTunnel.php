<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\IpsecTunnel
 *
 * @property int $tunnel_id
 * @property int $device_id
 * @property int $peer_port
 * @property string $peer_addr
 * @property string $local_addr
 * @property int $local_port
 * @property string $tunnel_name
 * @property string $tunnel_status
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel whereLocalAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel whereLocalPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel wherePeerAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel wherePeerPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel whereTunnelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel whereTunnelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\IpsecTunnel whereTunnelStatus($value)
 * @mixin \Eloquent
 */
class IpsecTunnel extends Model
{
    protected $table = 'ipsec_tunnels';
    protected $primaryKey = 'tunnel_id';
    public $timestamps = false;
}
