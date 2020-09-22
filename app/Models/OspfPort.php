<?php
/**
 * OspfPort.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link       http://librenms.org
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

/**
 * App\Models\OspfPort
 *
 * @property int $id
 * @property int $device_id
 * @property int $port_id
 * @property string $ospf_port_id
 * @property string $ospfIfIpAddress
 * @property int $ospfAddressLessIf
 * @property string $ospfIfAreaId
 * @property string|null $ospfIfType
 * @property string|null $ospfIfAdminStat
 * @property int|null $ospfIfRtrPriority
 * @property int|null $ospfIfTransitDelay
 * @property int|null $ospfIfRetransInterval
 * @property int|null $ospfIfHelloInterval
 * @property int|null $ospfIfRtrDeadInterval
 * @property int|null $ospfIfPollInterval
 * @property string|null $ospfIfState
 * @property string|null $ospfIfDesignatedRouter
 * @property string|null $ospfIfBackupDesignatedRouter
 * @property int|null $ospfIfEvents
 * @property string|null $ospfIfAuthKey
 * @property string|null $ospfIfStatus
 * @property string|null $ospfIfMulticastForwarding
 * @property string|null $ospfIfDemand
 * @property string|null $ospfIfAuthType
 * @property string|null $context_name
 * @property-read \App\Models\Device $device
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfAddressLessIf($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfAdminStat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfAuthKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfAuthType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfBackupDesignatedRouter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfDemand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfDesignatedRouter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfHelloInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfMulticastForwarding($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfPollInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfRetransInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfRtrDeadInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfRtrPriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfTransitDelay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfIfType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort whereOspfPortId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfPort wherePortId($value)
 * @mixin \Eloquent
 */
class OspfPort extends PortRelatedModel
{
    public $timestamps = false;
    protected $fillable = [
        'device_id',
        'port_id',
        'ospf_port_id',
        'context_name',
        'ospfIfIpAddress',
        'ospfAddressLessIf',
        'ospfIfAreaId',
        'ospfIfType',
        'ospfIfAdminStat',
        'ospfIfRtrPriority',
        'ospfIfTransitDelay',
        'ospfIfRetransInterval',
        'ospfIfHelloInterval',
        'ospfIfRtrDeadInterval',
        'ospfIfPollInterval',
        'ospfIfState',
        'ospfIfDesignatedRouter',
        'ospfIfBackupDesignatedRouter',
        'ospfIfEvents',
        'ospfIfAuthKey',
        'ospfIfStatus',
        'ospfIfMulticastForwarding',
        'ospfIfDemand',
        'ospfIfAuthType',
    ];

    // ---- Define Relationships ----

    public function device()
    {
        return $this->belongsTo(\App\Models\Device::class, 'device_id');
    }
}
