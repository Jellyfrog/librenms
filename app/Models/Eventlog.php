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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

use App\Models\Traits\Filterable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use LibreNMS\Enum\Severity;

class Eventlog extends DeviceRelatedModel
{
    use Filterable;
    use HasFactory;

    protected $table = 'eventlog';
    protected $primaryKey = 'event_id';
    public $timestamps = false;
    protected $fillable = ['datetime', 'device_id', 'message', 'type', 'reference', 'username', 'severity'];

    protected array $filterable = [
        'search',
        'device_id',
        'type',
        'severity',
        'username',
    ];

    /**
     * x-filter fields for the eventlog tables
     *
     * @param  int|null  $deviceId  the device the table is limited to, hides the device field
     * @return array<array<string, mixed>>
     */
    public static function filterFieldDefinitions(?int $deviceId = null): array
    {
        $fields = [
            [
                'key' => 'search',
                'label' => __('Message'),
                'type' => 'text',
                'search' => true,
            ],
        ];

        if ($deviceId === null) {
            $fields[] = [
                'key' => 'device_id',
                'label' => __('Device'),
                'type' => 'select',
                'endpoint' => route('ajax.select.device'),
            ];
        }

        return array_merge($fields, [
            [
                'key' => 'type',
                'label' => __('Type'),
                'type' => 'select',
                'endpoint' => route('ajax.select.eventlog'),
                'params' => array_filter(['field' => 'type', 'device' => $deviceId]),
            ],
            [
                'key' => 'severity',
                'label' => __('Severity'),
                'type' => 'select',
                'options' => collect(Severity::cases())->mapWithKeys(fn (Severity $severity) => [$severity->value => $severity->name])->all(),
            ],
            [
                'key' => 'username',
                'label' => __('User'),
                'type' => 'text',
            ],
        ]);
    }

    public function filterSearch(Builder $query, mixed $value, array $config): void
    {
        $this->applyFilterSearch(['message'], $query, $value, $config);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'severity' => Severity::class,
        ];
    }

    // ---- Helper Functions ----
    /**
     * This is used to be able to mock _log()
     *
     * @see _log()
     *
     * @param  string  $text  message describing the event
     * @param  Device|int|null  $device  related device
     * @param  string  $type  brief category for this event. Examples: sensor, state, stp, system, temperature, interface
     * @param  Severity  $severity  1: ok, 2: info, 3: notice, 4: warning, 5: critical, 0: unknown
     * @param  int|string|null  $reference  the id of the referenced entity.  Supported types: interface
     */
    public static function log(string $text, Device|int|null $device = null, ?string $type = null, Severity $severity = Severity::Info, int|string|null $reference = null): void
    {
        $model = app()->make(Eventlog::class);
        $model->_log($text, $device, $type, $severity, $reference);
    }

    /**
     * Log events to the event table
     */
    public function _log(string $text, Device|int|null $device = null, ?string $type = null, Severity $severity = Severity::Info, int|string|null $reference = null): void
    {
        $log = new static([
            'reference' => $reference,
            'type' => $type,
            'datetime' => Carbon::now(),
            'severity' => $severity,
            'message' => $text,
            'username' => (class_exists('\Auth') && Auth::check()) ? Auth::user()->username : '',
        ]);

        if (is_numeric($device)) {
            $log->device_id = $device;
        }

        if ($device instanceof Device) {
            $device->eventlogs()->save($log);
        } else {
            $log->save();
        }
    }

    // ---- Define Relationships ----

    public function related(): MorphTo
    {
        return $this->morphTo('related', 'type', 'reference');
    }
}
