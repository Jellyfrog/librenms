<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use LibreNMS\Enum\Severity;
use ValueError;

/**
 * Casts the alert rule severity string (ok, warning, critical) to a Severity.
 * Serializes back to the string so array/JSON output is unchanged.
 *
 * @implements CastsAttributes<Severity|null, Severity|string|null>
 */
class AlertRuleSeverity implements CastsAttributes, SerializesCastableAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Severity
    {
        return $value === null ? null : self::fromString($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return ($value instanceof Severity ? $value : self::fromString($value))->toAlertRule();
    }

    /**
     * Serialize the attribute when converting the model to an array.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value?->toAlertRule();
    }

    private static function fromString(string $value): Severity
    {
        $severity = Severity::fromAlertRule($value);

        if ($severity === Severity::Unknown) {
            throw new ValueError("\"$value\" is not a valid alert rule severity");
        }

        return $severity;
    }
}
