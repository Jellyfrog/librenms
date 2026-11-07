<?php

namespace LibreNMS\Enum;

/**
 * State of a service check, stored in services.service_status.
 *
 * The values are the Nagios/Monitoring Plugins exit codes, so alert rules
 * written against services.service_status (0 ok, 1 warning, 2 critical, 3 unknown)
 * keep working.
 */
enum ServiceStatus: int
{
    case Ok = 0;
    case Warning = 1;
    case Critical = 2;
    case Unknown = 3;

    /**
     * Convert a check plugin exit code to a status.
     *
     * Plugins may only return 0-3. Anything else (for example 126 "not executable",
     * 127 "command not found", or a negative code from a failed exec) means the
     * check could not determine the state of the service, so it is treated as Unknown,
     * as Icinga 2 does and as the Monitoring Plugins guidelines define UNKNOWN.
     * (Nagios Core instead reports such codes as CRITICAL; a failing check still
     * triggers the default "service up/down" rule, which alerts on any non-Ok status.)
     */
    public static function fromExitCode(int $exitCode): self
    {
        return self::tryFrom($exitCode) ?? self::Unknown;
    }

    /**
     * Human readable name, as used in the event log.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Warning => 'Warning',
            self::Critical => 'Critical',
            self::Unknown => 'Unknown',
        };
    }

    public function toSeverity(): Severity
    {
        return match ($this) {
            self::Ok => Severity::Ok,
            self::Warning => Severity::Warning,
            self::Critical => Severity::Error,
            self::Unknown => Severity::Unknown,
        };
    }

    /**
     * Bootstrap contextual class suffix (success, warning, danger, info).
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Ok => 'success',
            self::Warning => 'warning',
            self::Critical => 'danger',
            self::Unknown => 'info',
        };
    }

    /**
     * Bootstrap label class, e.g. label-success.
     */
    public function labelClass(): string
    {
        return 'label-' . $this->cssClass();
    }
}
