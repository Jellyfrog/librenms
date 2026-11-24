<?php

/**
 * GraylogController.php
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

namespace App\Http\Controllers\Table;

use App\ApiClients\GraylogApi;
use App\Facades\LibrenmsConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataTableRequest;
use App\Models\Device;
use App\Models\Syslog;
use DateInterval;
use DateTime;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;

/**
 * Graylog messages for x-graylog-table. Filters (stream, device_id, loglevel, range) are passed to the Graylog API
 */
class GraylogController extends Controller
{
    private readonly ?DateTimeZone $timezone;
    private array $deviceLinkCache = [];

    public function __construct()
    {
        $timezone = LibrenmsConfig::get('graylog.timezone');
        $this->timezone = $timezone ? new DateTimeZone($timezone) : null;
    }

    public function __invoke(DataTableRequest $request, GraylogApi $api): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewAny', Syslog::class); // Graylog replaces syslog

        if (! $api->isConfigured()) {
            return response()->json(['message' => __('Graylog is not configured')], 503);
        }

        $request->validate([
            'device' => 'nullable|integer',
            'filter.stream.eq' => 'nullable|alpha_num',
            'filter.device_id.eq' => 'nullable|integer',
            'filter.range.eq' => 'nullable|integer|min:0',
            'filter.loglevel.eq' => 'nullable|integer|between:0,7',
        ]);

        $filter = $request->filters();
        $deviceId = $request->integer('device') ?: (int) ($filter['device_id']['eq'] ?? 0);
        $device = $deviceId ? Device::find($deviceId) : null;
        $loglevel = $filter['loglevel']['eq'] ?? LibrenmsConfig::get('graylog.loglevel');
        $stream = $filter['stream']['eq'] ?? null;

        // the Graylog API needs a limit, every row means its maximum page
        $perPage = (int) $request->input('per_page', 25);
        $perPage = $perPage < 0 ? 1000 : $perPage;
        $page = max(1, $request->integer('page', 1));

        $sort = null;
        foreach ($request->sorts() as $field => $direction) {
            $sort = "$field:$direction";
            break;
        }

        try {
            $data = $api->query(
                $api->buildSimpleQuery($request->search(), $device) . ($loglevel !== null ? ' AND level: <=' . $loglevel : ''),
                (int) ($filter['range']['eq'] ?? 0),
                $perPage,
                ($page - 1) * $perPage,
                $sort,
                $stream ? "streams:$stream" : null,
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        $messages = array_map($this->formatMessage(...), $data['messages'] ?? []);

        return JsonResource::collection(new LengthAwarePaginator($messages, $data['total_results'] ?? 0, $perPage, $page));
    }

    private function formatMessage(array $message): array
    {
        if ($this->timezone) {
            $graylogTime = new DateTime($message['message']['timestamp']);
            $offset = $this->timezone->getOffset($graylogTime);

            $timeInterval = DateInterval::createFromDateString((string) $offset . 'seconds');
            $graylogTime->add($timeInterval);
            $displayTime = $graylogTime->format('Y-m-d H:i:s');
        } else {
            $displayTime = $message['message']['timestamp'];
        }

        $level = $message['message']['level'] ?? '';
        $facility = $message['message']['facility'] ?? '';

        return [
            'origin' => $this->deviceLinkFromSource($message['message']['gl2_remote_ip']),
            'severity' => $this->severityLabel($level),
            'id' => $message['message']['_id'] ?? null,
            'timestamp' => $displayTime,
            'source' => $this->deviceLinkFromSource($message['message']['source']),
            'message' => $message['message']['message'] ?? '',
            'facility' => is_numeric($facility) ? "($facility) " . __("syslog.facility.$facility") : $facility,
            'level' => (is_numeric($level) && $level >= 0) ? "($level) " . __("syslog.severity.$level") : $level,
        ];
    }

    private function severityLabel(string $severity): string
    {
        $map = [
            '0' => 'label-danger',
            '1' => 'label-danger',
            '2' => 'label-danger',
            '3' => 'label-danger',
            '4' => 'label-warning',
            '5' => 'label-info',
            '6' => 'label-info',
            '7' => 'label-default',
            '' => 'label-info',
        ];
        return $map[$severity] ?? 'label-info';
    }

    /**
     * Cache device lookups so we don't lookup for every entry
     */
    private function deviceLinkFromSource(?string $source): string
    {
        if (! $source) {
            return '';
        }

        if (! isset($this->deviceLinkCache[$source])) {
            $device = Device::findByIp($source) ?: Device::findByHostname($source);

            $this->deviceLinkCache[$source] = $device
                ? Blade::render('<x-device-link :device="$device"/>', ['device' => $device])
                : htmlspecialchars($source);
        }

        return $this->deviceLinkCache[$source];
    }
}
