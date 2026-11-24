<?php

namespace App\Http\Controllers\Table;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\StreamsCsv;
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\OutageResource;
use App\Models\Device;
use App\Models\DeviceOutage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LibreNMS\Util\Time;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OutagesController extends Controller
{
    use StreamsCsv;

    public function __invoke(DataTableRequest $request): AnonymousResourceCollection
    {
        $query = $this->query($request);
        $request->sortQuery($query, [
            'going_down', 'up_again',
            'device_id' => fn (Builder $q, string $direction) => $q->orderBy(Device::select('hostname')->whereColumn('devices.device_id', 'device_outages.device_id'), $direction),
        ], ['going_down' => 'desc']);

        return OutageResource::collection($request->paginate($query));
    }

    public function export(DataTableRequest $request): StreamedResponse
    {
        $rows = $this->query($request)->orderByDesc('going_down')->lazy()->map(fn (DeviceOutage $outage) => [
            $outage->device?->display,
            Carbon::createFromTimestamp($outage->going_down)->toIso8601ZuluString(),
            $outage->up_again ? Carbon::createFromTimestamp($outage->up_again)->toIso8601ZuluString() : '-',
            OutageResource::duration($outage)->format('%H:%I:%S'),
        ]);

        return $this->streamCsv('device_outages', ['Device Hostname', 'Start', 'End', 'Duration'], $rows);
    }

    /**
     * @return Builder<DeviceOutage>
     */
    private function query(DataTableRequest $request): Builder
    {
        $this->authorize('viewAny', DeviceOutage::class);
        $request->validate([
            'device' => 'nullable|int',
            'from' => 'nullable|date_or_relative',
            'to' => 'nullable|date_or_relative',
            'status' => 'nullable|in:current,previous,all',
        ]);

        $from_ts = Time::parseInput($request->from);
        $to_ts = Time::parseInput($request->to);

        return DeviceOutage::hasAccess($request->user())
            ->with('device')
            ->when($request->integer('device'), fn (Builder $q, int $deviceId) => $q->where('device_id', $deviceId))
            ->whereHas('device', function ($query): void {
                $query->where('disabled', 0);
            })
            ->when($from_ts || $to_ts, function ($query) use ($request, $from_ts, $to_ts): void {
                if ($request->status === 'current') {
                    // Current outages: only filter by start date within range
                    $this->applyDateRangeCondition($query, 'going_down', $from_ts, $to_ts);
                } elseif ($request->status === 'previous') {
                    // Previous outages: both start AND end must be within range
                    $query->where(function ($q) use ($from_ts, $to_ts): void {
                        $this->applyDateRangeCondition($q, 'going_down', $from_ts, $to_ts);
                        $this->applyDateRangeCondition($q, 'up_again', $from_ts, $to_ts);
                    });
                } else {
                    // All outages: use original logic (any overlap with range)
                    // Outage starts within range
                    $this->applyDateRangeCondition($query, 'going_down', $from_ts, $to_ts);

                    // Outage either has not ended, or end is within date range
                    $query->where(function ($q) use ($from_ts, $to_ts): void {
                        // OR outage ends within range (if it has ended)
                        $q->where(function ($subQuery) use ($from_ts, $to_ts): void {
                            $this->applyDateRangeCondition($subQuery, 'up_again', $from_ts, $to_ts);
                            $subQuery->orWhereNull('up_again');
                        });
                    });
                }
            })
            ->when($request->status === 'current', function ($query): void {
                $query->whereNull('up_again');
            })
            ->when($request->status === 'previous', function ($query): void {
                $query->whereNotNull('up_again');
            });
    }

    private function applyDateRangeCondition(Builder $query, string $column, ?int $from_ts, ?int $to_ts): void
    {
        if ($from_ts && $to_ts) {
            $query->whereBetween($column, [$from_ts, $to_ts]);
        } elseif ($from_ts) {
            $query->where($column, '>=', $from_ts);
        } elseif ($to_ts) {
            $query->where($column, '<=', $to_ts);
        }
    }
}
