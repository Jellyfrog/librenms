<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\Service
 *
 * @property int $service_id
 * @property int $device_id
 * @property string $service_ip
 * @property string $service_type
 * @property string $service_desc
 * @property string $service_param
 * @property int $service_ignore
 * @property int $service_status
 * @property int $service_changed
 * @property string $service_message
 * @property int $service_disabled
 * @property string $service_ds
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service isCritical()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service isDisabled()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service isIgnored()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service isOk()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service isWarning()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceChanged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceDs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceIgnore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceParam($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Service whereServiceType($value)
 * @mixin \Eloquent
 */
class Service extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'service_id';

    // ---- Query Scopes ----

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsOk($query)
    {
        return $query->where([
            ['service_ignore', '=', 0],
            ['service_disabled', '=', 0],
            ['service_status', '=', 0],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsCritical($query)
    {
        return $query->where([
            ['service_ignore', '=', 0],
            ['service_disabled', '=', 0],
            ['service_status', '=', 2],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsWarning($query)
    {
        return $query->where([
            ['service_ignore', '=', 0],
            ['service_disabled', '=', 0],
            ['service_status', '=', 1],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsIgnored($query)
    {
        return $query->where([
            ['service_ignore', '=', 1],
            ['service_disabled', '=', 0],
        ]);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsDisabled($query)
    {
        return $query->where('service_disabled', 1);
    }
}
