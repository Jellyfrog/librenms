<?php
/**
 * DeviceAttrib.php
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
 * App\Models\DeviceAttrib
 *
 * @property int $attrib_id
 * @property int $device_id
 * @property string $attrib_type
 * @property string $attrib_value
 * @property string $updated
 * @property-read \App\Models\Device $device
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceAttrib newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceAttrib newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceAttrib query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceAttrib whereAttribId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceAttrib whereAttribType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceAttrib whereAttribValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceAttrib whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceAttrib whereUpdated($value)
 * @mixin \Eloquent
 */
class DeviceAttrib extends DeviceRelatedModel
{
    protected $table = 'devices_attribs';
    protected $primaryKey = 'attrib_id';
    public $timestamps = false;
    protected $fillable = ['attrib_type', 'attrib_value'];
//    protected $casts = ['attrib_value' => 'array'];
}
