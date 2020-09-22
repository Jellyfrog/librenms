<?php
/**
 * app/Models/AlertRule.php
 *
 * Model for access to alert_rules table data
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
use LibreNMS\Enum\AlertState;

/**
 * App\Models\AlertRule
 *
 * @property int $id
 * @property string $rule
 * @property string $severity
 * @property string $extra
 * @property int $disabled
 * @property string $name
 * @property string $query
 * @property string $builder
 * @property string|null $proc
 * @property int $invert_map
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Alert[] $alerts
 * @property-read int|null $alerts_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Device[] $devices
 * @property-read int|null $devices_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule enabled()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule isActive()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereBuilder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereInvertMap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereProc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereQuery($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereRule($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertRule whereSeverity($value)
 * @mixin \Eloquent
 */
class AlertRule extends BaseModel
{
    public $timestamps = false;

    // ---- Query scopes ----

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('alert_rules.disabled', 0);
    }

    /**
     * Scope for only alert rules that are currently in alarm
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsActive($query)
    {
        return $query->enabled()
            ->join('alerts', 'alerts.rule_id', 'alert_rules.id')
            ->whereNotIn('alerts.state', [AlertState::CLEAR, AlertState::ACKNOWLEDGED, AlertState::RECOVERED]);
    }

    /**
     * Scope to filter rules for devices permitted to user
     * (do not use for admin and global read-only users)
     *
     * @param $query
     * @param User $user
     * @return mixed
     */
    public function scopeHasAccess($query, User $user)
    {
        if ($user->hasGlobalRead()) {
            return $query;
        }

        if (! $this->isJoined($query, 'alerts')) {
            $query->join('alerts', 'alerts.rule_id', 'alert_rules.id');
        }

        return $this->hasDeviceAccess($query, $user, 'alerts');
    }

    // ---- Define Relationships ----

    public function alerts()
    {
        return $this->hasMany(\App\Models\Alert::class, 'rule_id');
    }

    public function devices()
    {
        return $this->belongsToMany(\App\Models\Device::class, 'alert_device_map', 'device_id', 'device_id');
    }
}
