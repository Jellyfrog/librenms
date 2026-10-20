<?php

namespace LibreNMS\Enum;

use LibreNMS\Traits\EnumToArray;

/**
 * Keep the category enum in resources/definitions/schema/discovery_schema.json in sync with these cases
 */
enum SensorCategory: string
{
    use EnumToArray;

    case Stack = 'stack';
    case ControllerNodes = 'controller_nodes';
    case ApLicenses = 'ap_licenses';

    public function label(): string
    {
        return __("sensors.category.$this->value");
    }

    public function icon(): string
    {
        return match ($this) {
            self::Stack => 'th-large',
            self::ControllerNodes => 'server',
            self::ApLicenses => 'key',
        };
    }
}
