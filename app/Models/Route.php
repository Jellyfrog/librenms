<?php

namespace App\Models;

/**
 * App\Models\Route
 *
 * @property int $route_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $device_id
 * @property int $port_id
 * @property string|null $context_name
 * @property int $inetCidrRouteIfIndex
 * @property int $inetCidrRouteType
 * @property int $inetCidrRouteProto
 * @property int $inetCidrRouteNextHopAS
 * @property int $inetCidrRouteMetric1
 * @property string $inetCidrRouteDestType
 * @property string $inetCidrRouteDest
 * @property string $inetCidrRouteNextHopType
 * @property string $inetCidrRouteNextHop
 * @property string $inetCidrRoutePolicy
 * @property int $inetCidrRoutePfxLen
 * @property-read \App\Models\Device $device
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRouteDest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRouteDestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRouteIfIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRouteMetric1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRouteNextHop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRouteNextHopAS($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRouteNextHopType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRoutePfxLen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRoutePolicy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRouteProto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereInetCidrRouteType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route wherePortId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereRouteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Route whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Route extends DeviceRelatedModel
{
    protected $table = 'route';
    protected $primaryKey = 'route_id';
    public static $translateProto = [
        'undefined',
        'other',
        'local',
        'netmgmt',
        'icmp',
        'egp',
        'ggp',
        'hello',
        'rip',
        'isIs',
        'esIs',
        'ciscoIgrp',
        'bbnSpfIgp',
        'ospf',
        'bgp',
        'idpr',
        'ciscoEigrp',
        'dvmrp',
    ];

    public static $translateType = [
        'undefined',
        'other',
        'reject',
        'local',
        'remote',
        'blackhole',
    ];

    public $timestamps = true;

    // ---- Define Relationships ----
    public function device()
    {
        return $this->belongsTo(\App\Models\Device::class, 'device_id', 'device_id');
    }

    public function port()
    {
        return $this->belongsTo(\App\Models\Port::class, 'port_id', 'port_id');
    }
}
