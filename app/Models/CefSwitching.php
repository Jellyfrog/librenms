<?php
/**
 * CefSwitching.php
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
 * App\Models\CefSwitching
 *
 * @property int $cef_switching_id
 * @property int $device_id
 * @property int $entPhysicalIndex
 * @property string $afi
 * @property int $cef_index
 * @property string $cef_path
 * @property int $drop
 * @property int $punt
 * @property int $punt2host
 * @property int $drop_prev
 * @property int $punt_prev
 * @property int $punt2host_prev
 * @property int $updated
 * @property int $updated_prev
 * @property-read \App\Models\Device $device
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereAfi($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereCefIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereCefPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereCefSwitchingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereDrop($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereDropPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereEntPhysicalIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching wherePunt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching wherePunt2host($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching wherePunt2hostPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching wherePuntPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CefSwitching whereUpdatedPrev($value)
 * @mixin \Eloquent
 */
class CefSwitching extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $table = 'cef_switching';
    protected $primaryKey = 'cef_switching_id';
}
