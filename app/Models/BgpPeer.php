<?php

/**
 * BgpPeer.php
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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;

#[ApiResource(
    shortName: 'BgpPeer',
    operations: [
        new GetCollection(),
        new Get(),
    ],
    paginationItemsPerPage: 50,
)]
#[QueryParameter(key: 'device_id', filter: new EqualsFilter())]
#[QueryParameter(key: 'bgpPeerState', filter: new EqualsFilter())]
#[QueryParameter(key: 'bgpPeerRemoteAs', filter: new EqualsFilter())]
#[QueryParameter(key: 'bgpPeerDescr', filter: new PartialSearchFilter())]
#[QueryParameter(key: 'order', filter: new OrderFilter())]
class BgpPeer extends DeviceRelatedModel
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'bgpPeers';
    protected $primaryKey = 'bgpPeer_id';
    protected $fillable = [
        'vrf_id',
        'bgpPeerIdentifier',
        'bgpPeerRemoteAs',
        'bgpPeerState',
        'bgpPeerAdminStatus',
        'bgpLocalAddr',
        'bgpPeerRemoteAddr',
        'bgpPeerInUpdates',
        'bgpPeerOutUpdates',
        'bgpPeerInTotalMessages',
        'bgpPeerOutTotalMessages',
        'bgpPeerFsmEstablishedTime',
        'bgpPeerInUpdateElapsedTime',
        'bgpPeerDescr',
        'bgpPeerIface',
        'astext',
    ];
    // ---- Query scopes ----

    public function scopeInAlarm(Builder $query)
    {
        return $query->where(function (Builder $query): void {
            $query->where('bgpPeerAdminStatus', 'start')
                ->orWhere('bgpPeerAdminStatus', 'running');
        })->where('bgpPeerState', '!=', 'established');
    }
}
