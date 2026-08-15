<?php

namespace App\Models;

use App\Casts\CompressedJson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LibreNMS\Enum\AlertLogState;

class AlertLog extends DeviceRelatedModel
{
    use HasFactory;

    public const UPDATED_AT = null;
    public const CREATED_AT = 'time_logged';
    protected $table = 'alert_log';
    protected $fillable = [
        'device_id',
        'rule_id',
        'state',
        'details',
    ];
    protected $casts = [
        'state' => AlertLogState::class,
        'details' => CompressedJson::class,
        'time_logged' => 'datetime',
    ];

    /**
     * The alert this entry was logged for (matched by device_id + rule_id).
     *
     * Note: this relationship cannot be eager-loaded because Eloquent only
     * supports a single foreign key for BelongsTo, and the constraint on
     * rule_id uses whereColumn which is not available in eager-load queries.
     *
     * @return BelongsTo<Alert, $this>
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class, 'device_id', 'device_id')
            ->whereColumn('alerts.rule_id', 'alert_log.rule_id');
    }

    /**
     * @return BelongsTo<AlertRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'rule_id', 'id');
    }
}
