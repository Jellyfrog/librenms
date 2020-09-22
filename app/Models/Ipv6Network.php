<?php
/**
 * Ipv6Network.php
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

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Ipv6Network
 *
 * @property int $ipv6_network_id
 * @property string $ipv6_network
 * @property string|null $context_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Ipv6Address[] $ipv6
 * @property-read int|null $ipv6_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Network newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Network newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Network query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Network whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Network whereIpv6Network($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Ipv6Network whereIpv6NetworkId($value)
 * @mixin \Eloquent
 */
class Ipv6Network extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'ipv6_network_id';

    // ---- Define Relationships ----

    public function ipv6()
    {
        return $this->hasMany(\App\Models\Ipv6Address::class, 'ipv6_network_id');
    }
}
