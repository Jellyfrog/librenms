<?php
/**
 * Mpls.php
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
 * @copyright  2019 Vitali Kari
 * @author     Vitali Kari <vitali.kari@gmail.com>
 */

namespace App\Models;

/**
 * App\Models\Mpls
 *
 * @property int $lsp_id
 * @property int $vrf_oid
 * @property int $lsp_oid
 * @property int $device_id
 * @property string $mplsLspRowStatus
 * @property int|null $mplsLspLastChange
 * @property string $mplsLspName
 * @property string $mplsLspAdminState
 * @property string $mplsLspOperState
 * @property string $mplsLspFromAddr
 * @property string $mplsLspToAddr
 * @property string $mplsLspType
 * @property string $mplsLspFastReroute
 * @property int|null $mplsLspAge
 * @property int|null $mplsLspTimeUp
 * @property int|null $mplsLspTimeDown
 * @property int|null $mplsLspPrimaryTimeUp
 * @property int|null $mplsLspTransitions
 * @property int|null $mplsLspLastTransition
 * @property int|null $mplsLspConfiguredPaths
 * @property int|null $mplsLspStandbyPaths
 * @property int|null $mplsLspOperationalPaths
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereLspId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereLspOid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspAdminState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspConfiguredPaths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspFastReroute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspFromAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspLastChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspLastTransition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspOperState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspOperationalPaths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspPrimaryTimeUp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspRowStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspStandbyPaths($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspTimeDown($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspTimeUp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspToAddr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspTransitions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereMplsLspType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Mpls whereVrfOid($value)
 * @mixin \Eloquent
 */
class Mpls extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $table = 'mpls_lsps';
    protected $primaryKey = 'lsp_id';
}
