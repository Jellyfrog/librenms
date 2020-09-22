<?php
/**
 * VrfLite.php
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
 * @copyright  2019 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

/**
 * App\Models\VrfLite
 *
 * @property int $vrf_lite_cisco_id
 * @property int $device_id
 * @property string $context_name
 * @property string|null $intance_name
 * @property string|null $vrf_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VrfLite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VrfLite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VrfLite query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VrfLite whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VrfLite whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VrfLite whereIntanceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VrfLite whereVrfLiteCiscoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\VrfLite whereVrfName($value)
 * @mixin \Eloquent
 */
class VrfLite extends DeviceRelatedModel
{
    protected $table = 'vrf_lite_cisco';
    protected $primaryKey = 'vrf_lite_cisco_id';
    public $timestamps = false;
}
