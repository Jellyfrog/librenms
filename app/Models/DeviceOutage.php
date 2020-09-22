<?php
/**
 * DeviceOutage.php
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
 * @copyright  2020 Thomas Berberich
 * @author     Thomas Berberich <sourcehhdoctor@gmail.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// /**
 * App\Models\DeviceOutage
 *
 * @property int $device_id
 * @property int $going_down
 * @property int|null $up_again
 * @property-read \App\Models\Device $device
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceOutage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceOutage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceOutage query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceOutage whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceOutage whereGoingDown($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceOutage whereUpAgain($value)
 * @mixin \Eloquent
 */
class DeviceOutage extends Model
class DeviceOutage extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = null;
}
