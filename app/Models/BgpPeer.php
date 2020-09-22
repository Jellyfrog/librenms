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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link       http://librenms.org
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\BgpPeer
 *
 * @property int $bgpPeer_id
 * @property int $device_id
 * @property int|null $vrf_id
 * @property string $astext
 * @property string $bgpPeerIdentifier
 * @property int $bgpPeerRemoteAs
 * @property string $bgpPeerState
 * @property string $bgpPeerAdminStatus
 * @property int|null $bgpPeerLastErrorCode
 * @property int|null $bgpPeerLastErrorSubCode
 * @property string|null $bgpPeerLastErrorText
 * @property string $bgpLocalAddr
 * @property string $bgpPeerRemoteAddr
 * @property string $bgpPeerDescr
 * @property int $bgpPeerInUpdates
 * @property int $bgpPeerOutUpdates
 * @property int $bgpPeerInTotalMessages
 * @property int $bgpPeerOutTotalMessages
 * @property int $bgpPeerFsmEstablishedTime
 * @property int $bgpPeerInUpdateElapsedTime
 * @property string|null $context_name
 * @property-read \App\Models\Device $device
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer inAlarm()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereAstext($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpLocalAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerAdminStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerDescr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerFsmEstablishedTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerInTotalMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerInUpdateElapsedTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerInUpdates($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerLastErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerLastErrorSubCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerLastErrorText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerOutTotalMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerOutUpdates($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerRemoteAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerRemoteAs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereBgpPeerState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BgpPeer whereVrfId($value)
 * @mixin \Eloquent
 */
class BgpPeer extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $table = 'bgpPeers';
    protected $primaryKey = 'bgpPeer_id';

    // ---- Query scopes ----

    public function scopeInAlarm(Builder $query)
    {
        return $query->where(function (Builder $query) {
            $query->where('bgpPeerAdminStatus', 'start')
                ->orWhere('bgpPeerAdminStatus', 'running');
        })->where('bgpPeerState', '!=', 'established');
    }
}
