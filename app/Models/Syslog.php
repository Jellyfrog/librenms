<?php
/**
 * Syslog.php
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
 * App\Models\Syslog
 *
 * @property int|null $device_id
 * @property string|null $facility
 * @property string|null $priority
 * @property string|null $level
 * @property string|null $tag
 * @property string $timestamp
 * @property string|null $program
 * @property string|null $msg
 * @property int $seq
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog whereFacility($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog whereMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog whereProgram($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog whereSeq($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog whereTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Syslog whereTimestamp($value)
 * @mixin \Eloquent
 */
class Syslog extends DeviceRelatedModel
{
    protected $table = 'syslog';
    protected $primaryKey = 'seq';
    public $timestamps = false;
}
