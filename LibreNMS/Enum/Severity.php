<?php

namespace LibreNMS\Enum;

enum Severity: int
{
    case Unknown = 0;
    case Ok = 1;
    case Info = 2;
    case Notice = 3;
    case Warning = 4;
    case Error = 5;

    /**
     * Convert the severity string of an alert rule (ok, warning, critical) to a Severity.
     */
    public static function fromAlertRule(?string $severity): self
    {
        return match ($severity) {
            'ok' => self::Ok,
            'warning' => self::Warning,
            'critical' => self::Error,
            default => self::Unknown,
        };
    }

    /**
     * Convert to the severity string of an alert rule (ok, warning, critical).
     *
     * @throws \ValueError if this is not a valid alert rule severity
     */
    public function toAlertRule(): string
    {
        return match ($this) {
            self::Ok => 'ok',
            self::Warning => 'warning',
            self::Error => 'critical',
            default => throw new \ValueError("$this->name is not a valid alert rule severity"),
        };
    }

    /**
     * Severities that are valid for an alert rule.
     *
     * @return list<self>
     */
    public static function alertRuleCases(): array
    {
        return [self::Ok, self::Warning, self::Error];
    }
}
