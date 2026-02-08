<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use App\Casts\CompressedJson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LibreNMS\Enum\AlertLogState;

#[ApiResource(
    shortName: 'AlertLog',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'device_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'rule_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'state', filter: new EqualsFilter())]
#[QueryParameter(key: 'order', filter: new OrderFilter())]
class AlertLog extends DeviceRelatedModel
{
    use HasFactory;

    public const UPDATED_AT = null;
    public const CREATED_AT = 'time_logged';
    protected $table = 'alert_log';
    protected $casts = [
        'state' => AlertLogState::class,
        'details' => CompressedJson::class,
        'time_logged' => 'datetime',
    ];

    /**
     * @return BelongsTo<AlertRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'rule_id', 'id');
    }
}
