<?php
/**
 * OspfArea.php
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
 * App\Models\OspfArea
 *
 * @property int $id
 * @property int $device_id
 * @property string $ospfAreaId
 * @property string $ospfAuthType
 * @property string $ospfImportAsExtern
 * @property int $ospfSpfRuns
 * @property int $ospfAreaBdrRtrCount
 * @property int $ospfAsBdrRtrCount
 * @property int $ospfAreaLsaCount
 * @property int $ospfAreaLsaCksumSum
 * @property string $ospfAreaSummary
 * @property string $ospfAreaStatus
 * @property string|null $context_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereContextName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfAreaBdrRtrCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfAreaLsaCksumSum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfAreaLsaCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfAreaStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfAreaSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfAsBdrRtrCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfAuthType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfImportAsExtern($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\OspfArea whereOspfSpfRuns($value)
 * @mixin \Eloquent
 */
class OspfArea extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $fillable = [
        'device_id',
        'context_name',
        'ospfAreaId',
        'ospfAuthType',
        'ospfImportAsExtern',
        'ospfSpfRuns',
        'ospfAreaBdrRtrCount',
        'ospfAsBdrRtrCount',
        'ospfAreaLsaCount',
        'ospfAreaLsaCksumSum',
        'ospfAreaSummary',
        'ospfAreaStatus',
    ];
}
