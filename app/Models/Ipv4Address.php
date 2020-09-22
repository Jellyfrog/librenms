<?php
/**
 * Ipv4Address.php
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
 * App\Models\Ipv4Address
 *
 * @property int $ipv4_address_id
 * @property string $ipv4_address
 * @property int $ipv4_prefixlen
 * @property string $ipv4_network_id
 * @property int $port_id
 * @property string|null $context_name
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Address query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Address whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Address whereIpv4Address($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Address whereIpv4AddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Address whereIpv4NetworkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Address whereIpv4Prefixlen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv4Address wherePortId($value)
 * @mixin \Eloquent
 */
class Ipv4Address extends PortRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'ipv4_address_id';
}
