<?php

namespace App\Http\Resources\Table;

use App\Models\DeviceOutage;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LibreNMS\Util\Time;
use LibreNMS\Util\Url;

/**
 * @mixin DeviceOutage
 */
class OutageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->up_again ? 'label-success' : 'label-danger',
            'going_down' => $this->going_down ? Time::format($this->going_down, 'compact') : '',
            'up_again' => $this->up_again ? Time::format($this->up_again, 'compact') : '-',
            'device_id' => Url::modernDeviceLink($this->device),
            'duration' => self::duration($this->resource)->forHumans(['parts' => 2]),
        ];
    }

    public static function duration(DeviceOutage $outage): CarbonInterval
    {
        $start = Carbon::createFromTimestamp($outage->going_down);
        $end = $outage->up_again ? Carbon::createFromTimestamp($outage->up_again) : Carbon::now();

        return $end->diffAsCarbonInterval($start);
    }
}
