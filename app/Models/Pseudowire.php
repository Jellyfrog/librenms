<?php
/**
 * Pseudowire.php
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
 * App\Models\Pseudowire
 *
 * @property int $pseudowire_id
 * @property int $device_id
 * @property int $port_id
 * @property int $peer_device_id
 * @property int $peer_ldp_id
 * @property int $cpwVcID
 * @property int $cpwOid
 * @property string $pw_type
 * @property string $pw_psntype
 * @property int $pw_local_mtu
 * @property int $pw_peer_mtu
 * @property string $pw_descr
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire whereCpwOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire whereCpwVcID($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire wherePeerDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire wherePeerLdpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire wherePortId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire wherePseudowireId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire wherePwDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire wherePwLocalMtu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire wherePwPeerMtu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire wherePwPsntype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Pseudowire wherePwType($value)
 * @mixin \Eloquent
 */
class Pseudowire extends PortRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'pseudowire_id';
}
