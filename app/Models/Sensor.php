<?php

namespace App\Models;

/**
 * App\Models\Sensor
 *
 * @property int $sensor_id
 * @property int $sensor_deleted
 * @property string $sensor_class
 * @property int $device_id
 * @property string $poller_type
 * @property string $sensor_oid
 * @property string|null $sensor_index
 * @property string $sensor_type
 * @property string|null $sensor_descr
 * @property string|null $group
 * @property int $sensor_divisor
 * @property int $sensor_multiplier
 * @property float|null $sensor_current
 * @property float|null $sensor_limit
 * @property float|null $sensor_limit_warn
 * @property float|null $sensor_limit_low
 * @property float|null $sensor_limit_low_warn
 * @property int $sensor_alert
 * @property string $sensor_custom
 * @property string|null $entPhysicalIndex
 * @property string|null $entPhysicalIndex_measured
 * @property string $lastupdate
 * @property float|null $sensor_prev
 * @property string|null $user_func
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Eventlog[] $events
 * @property-read int|null $events_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereEntPhysicalIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereEntPhysicalIndexMeasured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereLastupdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor wherePollerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorAlert($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorCurrent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorCustom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorDivisor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorLimitLow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorLimitLowWarn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorLimitWarn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereSensorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Sensor whereUserFunc($value)
 * @mixin \Eloquent
 */
class Sensor extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'sensor_id';
    protected static $icons = [
        'airflow'              => 'angle-double-right',
        'ber'                  => 'sort-amount-desc',
        'charge'               => 'battery-half',
        'chromatic_dispersion' => 'indent',
        'cooling'              => 'thermometer-full',
        'count'                => 'hashtag',
        'current'              => 'bolt fa-flip-horizontal',
        'dbm'                  => 'sun-o',
        'delay'                => 'clock-o',
        'eer'                  => 'snowflake-o',
        'fanspeed'             => 'refresh',
        'frequency'            => 'line-chart',
        'humidity'             => 'tint',
        'load'                 => 'percent',
        'loss'                 => 'percentage',
        'power'                => 'power-off',
        'power_consumed'       => 'plug',
        'power_factor'         => 'calculator',
        'pressure'             => 'thermometer-empty',
        'quality_factor'       => 'arrows',
        'runtime'              => 'hourglass-half',
        'signal'               => 'wifi',
        'snr'                  => 'signal',
        'state'                => 'bullseye',
        'temperature'          => 'thermometer-three-quarters',
        'voltage'              => 'bolt',
        'waterflow'            => 'tint',
    ];

    // ---- Helper Functions ----

    public function classDescr()
    {
        $nice = collect([
            'ber' => 'BER',
            'dbm' => 'dBm',
            'eer' => 'EER',
            'snr' => 'SNR',
        ]);

        return $nice->get($this->sensor_class, ucwords(str_replace('_', ' ', $this->sensor_class)));
    }

    public function icon()
    {
        return collect(self::$icons)->get($this->sensor_class, 'delicius');
    }

    public static function getTypes()
    {
        return array_keys(self::$icons);
    }

    // for the legacy menu
    public static function getIconMap()
    {
        return self::$icons;
    }

    // ---- Define Relationships ----
    public function events()
    {
        return $this->morphMany(Eventlog::class, 'events', 'type', 'reference');
    }
}
