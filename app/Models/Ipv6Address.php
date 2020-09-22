<?php
/**
 * Ipv6Address.php
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
 * App\Models\Ipv6Address
 *
 * @property int $ipv6_address_id
 * @property string $ipv6_address
 * @property string $ipv6_compressed
 * @property int $ipv6_prefixlen
 * @property string $ipv6_origin
 * @property string $ipv6_network_id
 * @property int $port_id
 * @property string|null $context_name
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address whereIpv6Address($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address whereIpv6AddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address whereIpv6Compressed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address whereIpv6NetworkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address whereIpv6Origin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address whereIpv6Prefixlen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Address wherePortId($value)
 * @mixin \Eloquent
 */
class Ipv6Address extends PortRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'ipv6_address_id';
}
