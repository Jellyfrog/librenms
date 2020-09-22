<?php
/**
 * WirelessSensor.php
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
 * App\Models\WirelessSensor
 *
 * @property int $sensor_id
 * @property int $sensor_deleted
 * @property string $sensor_class
 * @property int $device_id
 * @property string|null $sensor_index
 * @property string $sensor_type
 * @property string|null $sensor_descr
 * @property int $sensor_divisor
 * @property int $sensor_multiplier
 * @property string $sensor_aggregator
 * @property float|null $sensor_current
 * @property float|null $sensor_prev
 * @property float|null $sensor_limit
 * @property float|null $sensor_limit_warn
 * @property float|null $sensor_limit_low
 * @property float|null $sensor_limit_low_warn
 * @property int $sensor_alert
 * @property string $sensor_custom
 * @property string|null $entPhysicalIndex
 * @property string|null $entPhysicalIndex_measured
 * @property string $lastupdate
 * @property string $sensor_oids
 * @property int|null $access_point_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereAccessPointId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereEntPhysicalIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereEntPhysicalIndexMeasured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereLastupdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorAggregator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorAlert($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorCurrent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorCustom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorDivisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorLimitLow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorLimitLowWarn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorLimitWarn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorOids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\WirelessSensor whereSensorType($value)
 * @mixin \Eloquent
 */
class WirelessSensor extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'sensors_id';

    // ---- Helper Functions ----

    public function classDescr()
    {
        return __("wireless.$this->sensor_class.short");
    }

    public function icon()
    {
        return collect(collect(\LibreNMS\Device\WirelessSensor::getTypes())
            ->get($this->sensor_class, []))
            ->get('icon', 'signal');
    }
}
