<?php
/**
 * OspfNbr.php
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
 * App\Models\OspfNbr
 *
 * @property int $id
 * @property int $device_id
 * @property int|null $port_id
 * @property string $ospf_nbr_id
 * @property string $ospfNbrIpAddr
 * @property int $ospfNbrAddressLessIndex
 * @property string $ospfNbrRtrId
 * @property int $ospfNbrOptions
 * @property int $ospfNbrPriority
 * @property string $ospfNbrState
 * @property int $ospfNbrEvents
 * @property int $ospfNbrLsRetransQLen
 * @property string $ospfNbmaNbrStatus
 * @property string $ospfNbmaNbrPermanence
 * @property string $ospfNbrHelloSuppressed
 * @property string|null $context_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbmaNbrPermanence($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbmaNbrStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrAddressLessIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrHelloSuppressed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrIpAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrLsRetransQLen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrPriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrRtrId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr whereOspfNbrState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfNbr wherePortId($value)
 * @mixin \Eloquent
 */
class OspfNbr extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $fillable = [
        'device_id',
        'port_id',
        'ospf_nbr_id',
        'context_name',
        'ospfNbrIpAddr',
        'ospfNbrAddressLessIndex',
        'ospfNbrRtrId',
        'ospfNbrOptions',
        'ospfNbrPriority',
        'ospfNbrState',
        'ospfNbrEvents',
        'ospfNbrLsRetransQLen',
        'ospfNbmaNbrStatus',
        'ospfNbmaNbrPermanence',
        'ospfNbrHelloSuppressed',
    ];
}
