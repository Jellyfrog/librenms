<?php
/**
 * OspfInstance.php
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
 * App\Models\OspfInstance
 *
 * @property int $id
 * @property int $device_id
 * @property int $ospf_instance_id
 * @property string $ospfRouterId
 * @property string $ospfAdminStat
 * @property string $ospfVersionNumber
 * @property string $ospfAreaBdrRtrStatus
 * @property string $ospfASBdrRtrStatus
 * @property int $ospfExternLsaCount
 * @property int $ospfExternLsaCksumSum
 * @property string $ospfTOSSupport
 * @property int $ospfOriginateNewLsas
 * @property int $ospfRxNewLsas
 * @property int|null $ospfExtLsdbLimit
 * @property int|null $ospfMulticastExtensions
 * @property int|null $ospfExitOverflowInterval
 * @property string|null $ospfDemandExtensions
 * @property string|null $context_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfASBdrRtrStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfAdminStat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfAreaBdrRtrStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfDemandExtensions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfExitOverflowInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfExtLsdbLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfExternLsaCksumSum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfExternLsaCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfInstanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfMulticastExtensions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfOriginateNewLsas($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfRouterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfRxNewLsas($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfTOSSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfInstance whereOspfVersionNumber($value)
 * @mixin \Eloquent
 */
class OspfInstance extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $fillable = [
        'device_id',
        'ospf_instance_id',
        'context_name',
        'ospfRouterId',
        'ospfAdminStat',
        'ospfVersionNumber',
        'ospfAreaBdrRtrStatus',
        'ospfASBdrRtrStatus',
        'ospfExternLsaCount',
        'ospfExternLsaCksumSum',
        'ospfTOSSupport',
        'ospfOriginateNewLsas',
        'ospfRxNewLsas',
        'ospfExtLsdbLimit',
        'ospfMulticastExtensions',
        'ospfExitOverflowInterval',
        'ospfDemandExtensions',
    ];
}
