<?php
/**
 * Eventlog.php
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

use Carbon\Carbon;
use LibreNMS\Enum\Alert;

/**
 * App\Models\Eventlog
 *
 * @property int $event_id
 * @property int|null $device_id
 * @property string $datetime
 * @property string|null $message
 * @property string|null $type
 * @property string|null $reference
 * @property string|null $username
 * @property int $severity
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $related
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog whereDatetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Eventlog whereUsername($value)
 * @mixin \Eloquent
 */
class Eventlog extends DeviceRelatedModel
{
    protected $table = 'eventlog';
    protected $primaryKey = 'event_id';
    public $timestamps = false;
    protected $fillable = ['datetime', 'device_id', 'message', 'type', 'reference', 'username', 'severity'];

    // ---- Helper Functions ----

    /**
     * Log events to the event table
     *
     * @param string $text message describing the event
     * @param Device $device related device
     * @param string $type brief category for this event. Examples: sensor, state, stp, system, temperature, interface
     * @param int $severity 1: ok, 2: info, 3: notice, 4: warning, 5: critical, 0: unknown
     * @param int $reference the id of the referenced entity.  Supported types: interface
     */
    public static function log($text, $device = null, $type = null, $severity = Alert::INFO, $reference = null)
    {
        $log = new static([
            'reference' => $reference,
            'type' => $type,
            'datetime' => Carbon::now(),
            'severity' => $severity,
            'message' => $text,
            'username'  => (class_exists('\Auth') && \Auth::check()) ? \Auth::user()->username : '',
        ]);

        if ($device instanceof Device) {
            $device->eventlogs()->save($log);
        } else {
            $log->save();
        }
    }

    // ---- Define Relationships ----

    public function related()
    {
        return $this->morphTo('related', 'type', 'reference');
    }
}
