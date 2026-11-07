<?php

namespace LibreNMS\Enum;

use ValueError;

enum AlertLogState: int
{
    case Clear = 0;
    case Active = 1;
    case Acknowledged = 2;
    case Worse = 3;
    case Better = 4;
    case Changed = 5;
    case Recovered = 6;

    /**
     * Map an alert/fault state (AlertState) to the state recorded in alert_log.
     * AlertState::RECOVERED shares 0 with AlertState::CLEAR; a log entry is only written for a
     * transition, so 0 always means the alert/fault recovered.
     */
    public static function fromAlertState(int $state): self
    {
        return match ($state) {
            AlertState::RECOVERED => self::Recovered,
            AlertState::ACTIVE => self::Active,
            AlertState::ACKNOWLEDGED => self::Acknowledged,
            AlertState::WORSE => self::Worse,
            AlertState::BETTER => self::Better,
            AlertState::CHANGED => self::Changed,
            default => throw new ValueError("Unknown alert state $state"),
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::Active, self::Worse, self::Better, self::Changed => true,
            default => false,
        };
    }

    public function asSeverity(): Severity
    {
        return match ($this) {
            self::Clear, self::Recovered => Severity::Ok,
            self::Active => Severity::Error,
            self::Acknowledged => Severity::Info,
            self::Worse, self::Changed => Severity::Warning,
            self::Better => Severity::Notice,
        };
    }
}
