<?php

/**
 * PortsNac.php
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;

#[ApiResource(
    shortName: 'NacEntry',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'device_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'mac_address', filter: new PartialSearchFilter())]
class PortsNac extends PortRelatedModel
{
    protected $table = 'ports_nac';
    protected $primaryKey = 'ports_nac_id';
    public $timestamps = true;
    protected $fillable = [
        'auth_id',
        'device_id',
        'port_id',
        'domain',
        'username',
        'mac_address',
        'ip_address',
        'vlan',
        'host_mode',
        'authz_status',
        'authz_by',
        'authc_status',
        'method',
        'timeout',
        'time_left',
        'time_elapsed',
    ];

    // ---- Define Relationships ----
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }
}
