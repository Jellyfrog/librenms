<?php

/**
 * app/Models/Alert.php
 *
 * Model for access to alerts table data
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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LibreNMS\Enum\AlertState;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;

#[ApiResource(
    shortName: 'Alert',
    operations: [
        new GetCollection(),
        new Get(policy: 'view'),
        new Patch(policy: 'update'),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'device_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'rule_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'state', filter: new EqualsFilter())]
#[QueryParameter(key: 'severity', filter: new EqualsFilter())]
#[QueryParameter(key: 'order', filter: new OrderFilter())]
class Alert extends Model
{
    public $timestamps = false;

    /**
     * @return array{info: 'array'}
     */
    protected function casts(): array
    {
        return [
            'info' => 'array',
        ];
    }

    // ---- Query scopes ----

    /**
     * Only select active alerts
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('state', '=', AlertState::ACTIVE);
    }

    /**
     * Only select active alerts
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeAcknowledged($query)
    {
        return $query->where('state', '=', AlertState::ACKNOWLEDGED);
    }

    // ---- Define Relationships ----
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\AlertRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'rule_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'devices_perms', 'device_id', 'user_id');
    }
}
