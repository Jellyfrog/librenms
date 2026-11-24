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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;

class Syslog extends DeviceRelatedModel
{
    use Filterable;
    use HasFactory;

    protected $table = 'syslog';
    protected $primaryKey = 'seq';
    public $timestamps = false;

    protected array $filterable = [
        'search',
        'device_id',
        'program',
        'priority',
        'level',
        'timestamp',
    ];

    /**
     * x-filter fields for the syslog tables
     *
     * @param  int|null  $deviceId  the device the table is limited to, hides the device field
     * @return array<array<string, mixed>>
     */
    public static function filterFieldDefinitions(?int $deviceId = null): array
    {
        $selectParams = fn (string $field) => array_filter(['field' => $field, 'device' => $deviceId]);

        return array_values(array_filter([
            ['key' => 'search', 'label' => __('Message'), 'type' => 'text', 'search' => true],
            $deviceId === null ? ['key' => 'device_id', 'label' => __('Device'), 'type' => 'select', 'endpoint' => route('ajax.select.device')] : null,
            ['key' => 'program', 'label' => __('Program'), 'type' => 'select', 'endpoint' => route('ajax.select.syslog'), 'params' => $selectParams('program')],
            ['key' => 'priority', 'label' => __('Priority'), 'type' => 'select', 'endpoint' => route('ajax.select.syslog'), 'params' => $selectParams('priority')],
            ['key' => 'timestamp', 'label' => __('Timestamp'), 'type' => 'date'],
        ]));
    }

    /**
     * x-filter filters from the request, old links use ?device=, ?program=, ?priority=, ?from= and ?to= instead of filter[]
     *
     * @return array<string, array<string, mixed>>
     */
    public static function filterFromRequest(Request $request): array
    {
        $filter = $request->array('filter');
        if ($deviceId = $request->integer('device')) {
            $filter['device_id'] ??= ['eq' => $deviceId];
        }
        foreach (['program', 'priority'] as $field) {
            if ($value = $request->input($field)) {
                $filter[$field] ??= ['eq' => $value];
            }
        }
        if ($request->filled('from') || $request->filled('to')) {
            $filter['timestamp'] ??= array_filter(['after' => $request->input('from'), 'before' => $request->input('to')]);
        }

        return $filter;
    }

    public function filterSearch(Builder $query, mixed $value, array $config): void
    {
        $this->applyFilterSearch(['msg'], $query, $value, $config);
    }
}
