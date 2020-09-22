<?php
/**
 * Applications.php
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

use LibreNMS\Util\StringHelpers;

/**
 * App\Models\Application
 *
 * @property int $app_id
 * @property int $device_id
 * @property string $app_type
 * @property string $app_state
 * @property int $discovered
 * @property string|null $app_state_prev
 * @property string $app_status
 * @property string $timestamp
 * @property string $app_instance
 * @property-read \App\Models\Device $device
 * @property-read mixed $show_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application whereAppInstance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application whereAppState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application whereAppStatePrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application whereAppStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application whereAppType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application whereDiscovered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Application whereTimestamp($value)
 * @mixin \Eloquent
 */
class Application extends DeviceRelatedModel
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key column name.
     *
     * @var string
     */
    protected $primaryKey = 'app_id';

    // ---- Helper Functions ----

    public function displayName()
    {
        return StringHelpers::niceCase($this->app_type);
    }

    public function getShowNameAttribute()
    {
        return StringHelpers::niceCase($this->app_type);
    }
}
