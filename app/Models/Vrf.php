<?php
/**
 * Vrf.php
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
 * App\Models\Vrf
 *
 * @property int $vrf_id
 * @property string $vrf_oid
 * @property string|null $vrf_name
 * @property int|null $bgpLocalAs
 * @property string|null $mplsVpnVrfRouteDistinguisher
 * @property string $mplsVpnVrfDescription
 * @property int $device_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf whereBgpLocalAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf whereMplsVpnVrfDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf whereMplsVpnVrfRouteDistinguisher($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf whereVrfId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf whereVrfName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Vrf whereVrfOid($value)
 * @mixin \Eloquent
 */
class Vrf extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $table = 'vrfs';
    protected $primaryKey = 'vrf_id';
}
