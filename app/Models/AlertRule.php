<?php

/**
 * app/Models/AlertRule.php
 *
 * Model for access to alert_rules table data
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2016 Neil Lathwood
 * @author     Neil Lathwood <neil@lathwood.co.uk>
 */

namespace App\Models;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LibreNMS\Enum\AlertState;

#[ApiResource(
    shortName: 'AlertRule',
    operations: [
        new GetCollection(),
        new Get(policy: 'view'),
        new Post(policy: 'create'),
        new Patch(policy: 'update'),
        new Delete(policy: 'delete'),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'name', filter: new PartialSearchFilter())]
#[QueryParameter(key: 'severity', filter: new EqualsFilter())]
#[QueryParameter(key: 'disabled', filter: new EqualsFilter())]
#[QueryParameter(key: 'order', filter: new OrderFilter())]
class AlertRule extends BaseModel
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::deleting(function (AlertRule $rule): void {
            $rule->alerts()->delete();
            $rule->logs()->delete();
            $rule->templateMaps()->delete();

            $rule->devices()->detach();
            $rule->groups()->detach();
            $rule->locations()->detach();
            $rule->transportSingles()->detach();
            $rule->transportGroups()->detach();
        });
    }

    protected $fillable = [
        'severity',
        'extra',
        'disabled',
        'name',
        'proc',
        'notes',
        'query',
        'builder',
        'invert_map',
    ];

    protected $casts = [
        'builder' => 'array',
        'extra' => 'array',
    ];

    // ---- Query scopes ----

    /**
     * @param  Builder<AlertRule>  $query
     * @return Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('alert_rules.disabled', 0);
    }

    /**
     * Scope for only alert rules that are currently in alarm
     *
     * @param  Builder<AlertRule>  $query
     * @return Builder
     */
    public function scopeIsActive($query)
    {
        return $query->enabled()
            ->join('alerts', 'alerts.rule_id', 'alert_rules.id')
            ->whereNotIn('alerts.state', [AlertState::CLEAR, AlertState::ACKNOWLEDGED, AlertState::RECOVERED]);
    }

    /**
     * Scope to filter rules for devices permitted to user
     * (do not use for admin and global read-only users)
     *
     * @param  Builder<AlertRule>  $query
     * @param  User  $user
     * @return mixed
     */
    public function scopeHasAccess($query, User $user)
    {
        if ($user->hasGlobalRead()) {
            return $query;
        }

        if (! $this->isJoined($query, 'alerts')) {
            $query->join('alerts', 'alerts.rule_id', 'alert_rules.id');
        }

        return $this->hasDeviceAccess($query, $user, 'alerts');
    }

    // ---- Define Relationships ----

    /**
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'rule_id');
    }

    /**
     * @return HasMany<AlertLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AlertLog::class, 'rule_id');
    }

    /**
     * @return HasMany<AlertTemplateMap, $this>
     */
    public function templateMaps(): HasMany
    {
        return $this->hasMany(AlertTemplateMap::class, 'alert_rule_id');
    }

    /**
     * @return BelongsToMany<Device, $this>
     */
    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'alert_device_map', 'rule_id', 'device_id');
    }

    /**
     * @return BelongsToMany<DeviceGroup, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(DeviceGroup::class, 'alert_group_map', 'rule_id', 'group_id');
    }

    /**
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'alert_location_map', 'rule_id');
    }

    /**
     * @return BelongsToMany<AlertTransport, $this>
     */
    public function transportSingles(): BelongsToMany
    {
        return $this->belongsToMany(AlertTransport::class, 'alert_transport_map', 'rule_id', 'transport_or_group_id')
            ->withPivot('target_type')
            ->wherePivot('target_type', 'single');
    }

    /**
     * @return BelongsToMany<AlertTransportGroup, $this>
     */
    public function transportGroups(): BelongsToMany
    {
        return $this->belongsToMany(AlertTransportGroup::class, 'alert_transport_map', 'rule_id', 'transport_or_group_id')
            ->withPivot('target_type')
            ->wherePivot('target_type', 'group');
    }
}
