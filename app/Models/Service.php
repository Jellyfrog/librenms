<?php

namespace App\Models;

use App\Observers\ServiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LibreNMS\Enum\ServiceStatus;

#[ObservedBy([ServiceObserver::class])]
class Service extends DeviceRelatedModel
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'service_id';
    protected $fillable = [
        'service_id',
        'device_id',
        'service_ip',
        'service_type',
        'service_desc',
        'service_param',
        'service_ignore',
        'service_status',
        'service_changed',
        'service_message',
        'service_disabled',
        'service_ds',
        'service_template_id',
        'service_name',
    ];

    // ---- Accessors/Mutators ----

    /**
     * Like an enum cast, but tolerant of out-of-range exit codes stored by older versions.
     *
     * @return Attribute<ServiceStatus, ServiceStatus|int|string>
     */
    protected function serviceStatus(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ServiceStatus::fromExitCode((int) $value),
            set: fn ($value) => $value instanceof ServiceStatus ? $value->value : ServiceStatus::fromExitCode((int) $value)->value,
        );
    }

    // ---- Query Scopes ----

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIsActive($query)
    {
        return $query->where([
            ['service_ignore', '=', 0],
            ['service_disabled', '=', 0],
        ]);
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIsOk($query)
    {
        return $query->where([
            ['service_ignore', '=', 0],
            ['service_disabled', '=', 0],
            ['service_status', '=', ServiceStatus::Ok->value],
        ]);
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIsCritical($query)
    {
        return $query->where([
            ['service_ignore', '=', 0],
            ['service_disabled', '=', 0],
            ['service_status', '=', ServiceStatus::Critical->value],
        ]);
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIsWarning($query)
    {
        return $query->where([
            ['service_ignore', '=', 0],
            ['service_disabled', '=', 0],
            ['service_status', '=', ServiceStatus::Warning->value],
        ]);
    }

    /**
     * @param  Builder  $query
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
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeIsDisabled($query)
    {
        return $query->where('service_disabled', 1);
    }
}
