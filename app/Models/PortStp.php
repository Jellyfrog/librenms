<?php

namespace App\Models;

/**
 * App\Models\PortStp
 *
 * @property int $port_stp_id
 * @property int $device_id
 * @property int $port_id
 * @property int $priority
 * @property string $state
 * @property string $enable
 * @property int $pathCost
 * @property string $designatedRoot
 * @property int $designatedCost
 * @property string $designatedBridge
 * @property int $designatedPort
 * @property int $forwardTransitions
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp whereDesignatedBridge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp whereDesignatedCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp whereDesignatedPort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp whereDesignatedRoot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp whereEnable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp whereForwardTransitions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp wherePathCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp wherePortId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp wherePortStpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortStp whereState($value)
 * @mixin \Eloquent
 */
class PortStp extends PortRelatedModel
{
    protected $table = 'ports_stp';
    protected $primaryKey = 'port_stp_id';
    public $timestamps = false;
}
