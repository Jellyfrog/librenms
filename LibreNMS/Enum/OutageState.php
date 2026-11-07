<?php

namespace LibreNMS\Enum;

use App\Facades\LibrenmsConfig;
use App\Models\Device;

/**
 * Device availability state as far as device_outages is concerned.
 */
enum OutageState: string
{
    case Up = 'up';
    case Down = 'down';
    case DownInMaintenance = 'down_in_maintenance'; // down, but not counted as an outage
    case Disabled = 'disabled';

    public static function fromDevice(Device $device): self
    {
        if ($device->disabled) {
            return self::Disabled;
        }

        if ($device->status) {
            return self::Up;
        }

        if (LibrenmsConfig::get('graphing.availability_consider_maintenance') && $device->isUnderMaintenance()) {
            return self::DownInMaintenance;
        }

        return self::Down;
    }

    /**
     * The one place that decides what happens to the outage record.
     *
     * @param  bool  $outageOpen  whether the device has an open outage (the previous state)
     */
    public function outageAction(bool $outageOpen): OutageAction
    {
        return match ($this) {
            self::Down => $outageOpen ? OutageAction::None : OutageAction::Open,
            // a disabled device is no longer monitored, so we can't claim it is still down
            self::Up, self::Disabled => $outageOpen ? OutageAction::Close : OutageAction::None,
            // don't start counting during maintenance, an outage that began before maintenance stays open
            self::DownInMaintenance => OutageAction::None,
        };
    }
}
