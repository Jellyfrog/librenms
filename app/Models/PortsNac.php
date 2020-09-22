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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link       http://librenms.org
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

/**
 * App\Models\PortsNac
 *
 * @property int $ports_nac_id
 * @property string $auth_id
 * @property int $device_id
 * @property int $port_id
 * @property string $domain
 * @property string $username
 * @property string $mac_address
 * @property string $ip_address
 * @property string $host_mode
 * @property string $authz_status
 * @property string $authz_by
 * @property string $authc_status
 * @property string $method
 * @property string $timeout
 * @property string|null $time_left
 * @property int|null $vlan
 * @property string|null $time_elapsed
 * @property-read \App\Models\Device $device
 * @property-read \App\Models\Port $port
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereAuthId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereAuthcStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereAuthzBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereAuthzStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereHostMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereMacAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac wherePortId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac wherePortsNacId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereTimeElapsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereTimeLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereTimeout($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\PortsNac whereVlan($value)
 * @mixin \Eloquent
 */
class PortsNac extends PortRelatedModel
{
    protected $table = 'ports_nac';
    protected $primaryKey = 'ports_nac_id';
    public $timestamps = false;
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

    public function device()
    {
        return $this->belongsTo(\App\Models\Device::class, 'device_id', 'device_id');
    }
}
