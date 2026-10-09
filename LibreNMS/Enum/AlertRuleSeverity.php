<?php

/*
 * AlertRuleSeverity.php
 *
 * Severity of an alert rule (alert_rules.severity / alert_faults.severity)
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       https://www.librenms.org
 */

namespace LibreNMS\Enum;

enum AlertRuleSeverity: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Critical = 'critical';

    /**
     * Ordinal of the severity, higher is worse. Matches the MySQL enum index of alert_rules.severity.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Ok => 1,
            self::Warning => 2,
            self::Critical => 3,
        };
    }

    /**
     * Convert to the generic UI Severity.
     */
    public function toSeverity(): Severity
    {
        return match ($this) {
            self::Ok => Severity::Ok,
            self::Warning => Severity::Warning,
            self::Critical => Severity::Error,
        };
    }
}
