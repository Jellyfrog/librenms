<?php
/**
 * Component.php
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
 * App\Models\Component
 *
 * @property int $id ID for each component, unique index
 * @property int $device_id device_id from the devices table
 * @property string $type name from the component_type table
 * @property string|null $label Display label for the component
 * @property int $status The status of the component, retreived from the device
 * @property int $disabled Should this component be polled
 * @property int $ignore Should this component be alerted on
 * @property string|null $error Error message if in Alert state
 * @property-read \App\Models\Device $device
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ComponentStatusLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ComponentPref[] $prefs
 * @property-read int|null $prefs_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component whereDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component whereIgnore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Component whereType($value)
 * @mixin \Eloquent
 */
class Component extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $table = 'component';
    protected $fillable = ['device_id', 'type', 'label', 'status', 'disabled', 'ignore', 'error'];

    // ---- Accessors/Mutators ----

    public function setStatusAttribute($status)
    {
        $this->attributes['status'] = (int) $status;
    }

    public function setDisabledAttribute($disabled)
    {
        $this->attributes['disabled'] = (int) $disabled;
    }

    public function setIgnoreAttribute($ignore)
    {
        $this->attributes['ignore'] = (int) $ignore;
    }

    // ---- Define Relationships ----

    public function logs()
    {
        return $this->hasMany(\App\Models\ComponentStatusLog::class, 'component_id', 'id');
    }

    public function prefs()
    {
        return $this->hasMany(\App\Models\ComponentPref::class, 'component', 'id');
    }
}
