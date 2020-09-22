<?php
/**
 * app/Models/Alert.php
 *
 * Model for access to alerts table data
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
 * @copyright  2016 Neil Lathwood
 * @author     Neil Lathwood <neil@lathwood.co.uk>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LibreNMS\Enum\AlertState;

/**
 * App\Models\Alert
 *
 * @property int $id
 * @property int $device_id
 * @property int $rule_id
 * @property int $state
 * @property int $alerted
 * @property int $open
 * @property string|null $note
 * @property string $timestamp
 * @property string $info
 * @property-read \App\Models\Device $device
 * @property-read \App\Models\AlertRule $rule
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert acknowledged()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert active()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert whereAlerted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert whereInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert whereOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert whereRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Alert whereTimestamp($value)
 * @mixin \Eloquent
 */
class Alert extends Model
{
    public $timestamps = false;

    // ---- Query scopes ----

    /**
     * Only select active alerts
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('state', '=', AlertState::ACTIVE);
    }

    /**
     * Only select active alerts
     * @param Builder $query
     * @return Builder
     */
    public function scopeAcknowledged($query)
    {
        return $query->where('state', '=', AlertState::ACKNOWLEDGED);
    }

    // ---- Define Relationships ----

    public function device()
    {
        return $this->belongsTo(\App\Models\Device::class, 'device_id');
    }

    public function rule()
    {
        return $this->belongsTo(\App\Models\AlertRule::class, 'rule_id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'devices_perms', 'device_id', 'user_id');
    }
}
