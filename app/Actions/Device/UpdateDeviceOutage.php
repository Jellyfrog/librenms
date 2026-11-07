<?php

namespace App\Actions\Device;

use App\Models\Device;
use App\Models\DeviceOutage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LibreNMS\Enum\OutageAction;
use LibreNMS\Enum\OutageState;

class UpdateDeviceOutage
{
    /**
     * Bring device_outages in line with the device's current state.
     * Safe to call repeatedly: it opens at most one outage and only closes open ones.
     * Timestamps are unix ints, matching the device_outages schema.
     */
    public function execute(Device $device): OutageAction
    {
        $state = OutageState::fromDevice($device);

        if ($state === OutageState::DownInMaintenance) {
            return OutageAction::None; // never acts, skip the query
        }

        $action = $state->outageAction($this->openOutages($device)->exists());

        match ($action) {
            OutageAction::Open => $this->open($device),
            OutageAction::Close => $this->openOutages($device)->update(['up_again' => time()]),
            OutageAction::None => null,
        };

        return $action;
    }

    /**
     * Open an outage unless another process already has.
     * Locking the device row serializes concurrent openers (PingCheck vs PollDevice) for this device;
     * the one that waited re-checks after the other committed and does nothing.
     */
    private function open(Device $device): void
    {
        try {
            DB::transaction(function () use ($device): void {
                Device::whereKey($device->device_id)->lockForUpdate()->value('device_id');

                if (! $this->openOutages($device)->exists()) {
                    $device->outages()->save(new DeviceOutage(['going_down' => time()]));
                }
            });
        } catch (UniqueConstraintViolationException) {
            // unique (device_id, going_down): an outage was already opened for this device in this second
        }
    }

    /**
     * @return HasMany<DeviceOutage, Device>
     */
    private function openOutages(Device $device): HasMany
    {
        return $device->outages()->whereNull('up_again');
    }
}
