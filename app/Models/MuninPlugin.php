<?php
/**
 * MuninPlugin.php
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
 * App\Models\MuninPlugin
 *
 * @property int $mplug_id
 * @property int $device_id
 * @property string $mplug_type
 * @property string|null $mplug_instance
 * @property string|null $mplug_category
 * @property string|null $mplug_title
 * @property string|null $mplug_info
 * @property string|null $mplug_vlabel
 * @property string|null $mplug_args
 * @property int $mplug_total
 * @property int $mplug_graph
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugArgs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugGraph($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugInstance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\MuninPlugin whereMplugVlabel($value)
 * @mixin \Eloquent
 */
class MuninPlugin extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'mplug_id';
}
