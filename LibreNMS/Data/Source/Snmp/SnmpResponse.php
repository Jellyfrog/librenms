<?php

/**
 * SnmpResponse.php
 *
 * Base SNMP response object storing key-value pairs.
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace LibreNMS\Data\Source\Snmp;

use App\Facades\LibrenmsConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LibreNMS\Enum\SnmpQuickPrint;
use LibreNMS\Exceptions\SnmpException;
use LibreNMS\Util\Oid;
use LibreNMS\Util\StringHelpers;
use Log;

class SnmpResponse implements \Stringable
{
    protected const KEY_VALUE_DELIMITER = ' = ';
    protected const STDERR_ERROR_REGEX = '/(Timeout: No Response from .*|snmp\w+: Timeout|Unknown user name|Authentication failure|Error: OID not increasing: .*)/';
    protected const BAD_STRING_REGEX = '/(No Such Instance|No Such Object|No more variables left).*/';
    protected const BAD_VALUE_REGEX = '/(No Such Instance|No Such Object|at this OID|this MIB View|End of MIB| = NULL$).*/';
    protected const WRONG_TYPE_REGEX = '/Wrong Type \(should be .*\): /';

    /**
     * @var array<string, string> <oid, value>
     */
    protected array $values = [];
    protected ?string $badString = null;
    protected ?string $errorMessage = null;

    /**
     * @param  array<string, string>  $rawValues
     * @param  array<int, string>  $command
     * @param  SnmpQueryOptions|null  $options  output options of the query, raw() prints the values like net-snmp would
     */
    public function __construct(
        protected array $rawValues = [],
        public readonly string $stderr = '',
        public readonly int $exitCode = 0,
        public readonly array $command = [],
        protected readonly ?SnmpQueryOptions $options = null,
    ) {
        foreach ($this->rawValues as $oid => $val) {
            if (preg_match(self::BAD_STRING_REGEX, (string) $val, $matches)) {
                $this->badString ??= $matches[0];
                continue;
            }
            if (preg_match(self::BAD_VALUE_REGEX, (string) $val)) {
                continue;
            }
            $this->values[$oid] = (string) $val;
        }
    }

    public function raw(): string
    {
        $lines = [];
        $source = ! empty($this->rawValues) ? $this->rawValues : $this->values;
        foreach ($source as $oid => $val) {
            $lines[] = match (true) {
                $oid === '' || $this->options?->valueOnly => (string) $val,
                $this->options?->quickPrint == SnmpQuickPrint::NoEquals => "$oid $val",
                default => "$oid = $val",
            };
        }

        return ! empty($lines) ? implode("\n", $lines) . "\n" : ($this->errorMessage ?? '');
    }

    /**
     * Create a response from values as printed by net-snmp (php-snmp), values() are cleaned like the cli output
     *
     * raw() keeps the values as printed, like the cli output that legacy code parses:
     * quoted strings (an empty string is "") and ASN NULL rows. Legacy snmpwalk_group() counts
     * the NULL rows (e.g. rfc1628 names a single line "Output Phase 1" when the other lines
     * are NULL). That is arguably wrong, but the cli output is the reference for now.
     *
     * @param  array<string, mixed>  $printed
     * @param  array<int, string>  $command
     */
    public static function fromPrinted(array $printed, string $stderr = '', int $exitCode = 0, array $command = [], ?SnmpQueryOptions $options = null): self
    {
        $response = new self(array_map(strval(...), $printed), $stderr, $exitCode, $command, $options);

        $unescape = (bool) LibrenmsConfig::get('snmp.unescape');
        foreach ($response->values as $oid => $value) {
            $value = self::normalizeValue($value, $unescape);
            if ($value === null) {
                unset($response->values[$oid]);
            } else {
                $response->values[$oid] = $value;
            }
        }

        return $response;
    }

    /**
     * Clean a value as printed by net-snmp: strip the Wrong Type prefix, quotes and escapes, and fix the encoding
     *
     * @return string|null null for values to drop (ASN NULL)
     */
    public static function normalizeValue(string $value, bool $unescape = false, bool $inferEncoding = true): ?string
    {
        $value = (string) preg_replace(self::WRONG_TYPE_REGEX, '', $value);
        if ($value === 'NULL') {
            return null;
        }

        if ($unescape) {
            $value = stripslashes($value);
        }

        if (strlen($value) > 1 && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            // remove only the surrounding quotes, an escaped quote at the end belongs to the value
            $value = trim(stripslashes(substr($value, 1, -1)), " \n\r");
        } else {
            $value = trim($value);
        }

        return $inferEncoding ? (string) StringHelpers::inferEncoding($value) : $value;
    }

    public function isValid(bool $ignore_partial = false): bool
    {
        if ($ignore_partial) {
            return ! empty($this->values());
        }

        $this->errorMessage = '';

        $badString = $this->getStderrError() ?? $this->findBadString();
        $isEmpty = $this->isEmpty();

        if ($badString !== null || $isEmpty) {
            $this->errorMessage = $badString ?? 'Empty Output';
            Log::debug(sprintf('SNMP query failed. Exit Code: %s Empty: %s Bad String: %s', $this->exitCode, var_export($isEmpty, true), $badString ?? 'not found'));

            return false;
        }

        return true;
    }

    protected function getStderrError(): ?string
    {
        if (! empty($this->stderr) && preg_match(self::STDERR_ERROR_REGEX, $this->stderr, $errors)) {
            return $errors[0];
        }

        return null;
    }

    protected function findBadString(): ?string
    {
        return $this->badString;
    }

    /**
     * The agent did not answer or rejected the request, as opposed to having no data for it
     */
    public function hasConnectionError(): bool
    {
        return (bool) preg_match('/Timeout: No Response from|snmp\w+: Timeout|No response from|Unknown user name|Authentication failure|authorizationError/i', $this->stderr);
    }

    /**
     * For queries whose result must be known, for example before removing data the device no longer reports
     *
     * @throws SnmpException
     */
    public function throwIfConnectionError(): static
    {
        if ($this->hasConnectionError()) {
            throw new SnmpException(trim($this->stderr));
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    /**
     * Get the error message if any
     */
    public function getErrorMessage(): string
    {
        if (empty($this->errorMessage)) {
            $this->isValid(); // if no error message, double check.
        }

        return $this->errorMessage ?? '';
    }

    /**
     * Gets the first value of this response.
     * If an oid or list of oids is given, return the first one found.
     * If forceNumeric is set, force the search to use numeric oids even if textual oids are given
     *
     * @throws \LibreNMS\Exceptions\InvalidOidException
     */
    public function value(array|string $oids = [], bool $forceNumeric = false): string
    {
        $values = $this->values();

        if (empty($oids)) {
            return Arr::first($values, default: '');
        }

        $oids = Arr::wrap($oids);

        // search for an exact match
        foreach ($oids as $oid) {
            if ($forceNumeric) {
                // translate all to numeric to make it easier to match
                $oid = Oid::of($oid)->toNumeric();
            }

            if (isset($values[$oid]) && $values[$oid] !== '') {
                return $values[$oid];
            }

            // if this is a textual oid without an index, match the first one at any index
            if (! preg_match('/[.[]\d+]?$/', (string) $oid)) {
                foreach ($values as $key => $value) {
                    if (preg_match('/^' . preg_quote((string) $oid, '/') . '[.[]/', (string) $key) && $value !== '') {
                        return $value;
                    }
                }
            }
        }

        // try to match table format
        foreach ($oids as $oid) {
            $dot_index_oid = preg_replace('/\.([^.]+)/', '[$1]', (string) $oid);
            // if new oid is different and exists and is not an empty string
            if ($dot_index_oid !== $oid && isset($values[$dot_index_oid]) && $values[$dot_index_oid] !== '') {
                return $values[$dot_index_oid];
            }
        }

        return '';
    }

    public function values(): array
    {
        return $this->values;
    }

    /**
     * Create a key to value pair for an OID
     * You may omit $oid if there is only one $oid in the walk
     */
    public function pluck(?string $oid = null): array
    {
        $output = [];
        $oid ??= '[a-zA-Z0-9:.-]+';
        $regex = "/^{$oid}[[.]([\d.[\]]+?)]?$/";

        foreach ($this->values() as $key => $value) {
            if (preg_match($regex, (string) $key, $matches)) {
                $output_key = str_replace('][', '.', $matches[1]);
                $output[$output_key] = $value;
            }
        }

        return $output;
    }

    /**
     * Group values by index as specified by $index_count
     * Useful when dealing with numeric oids
     * (By default this counts from right to left, using a negative index count will count from left to right)
     */
    public function groupByIndex(int $index_count = 1, array &$array = []): array
    {
        foreach ($this->values() as $oid => $value) {
            $parts = $this->getOidParts(ltrim((string) $oid, '.')); // trim leftmost . so negative counts work as expected
            $suffix = array_slice($parts, -$index_count);
            $index = implode('.', $suffix);

            $array[$index][$oid] = $value;
        }

        return $array;
    }

    /**
     * Separate the index from the OID name
     * Insert into array as index => oidName
     */
    public function valuesByIndex(array &$array = []): array
    {
        foreach ($this->values() as $oid => $value) {
            $parts = $this->getOidParts($oid);
            $name = array_shift($parts);
            $index = implode('.', $parts);

            $array[$index][$name] = $value;
        }

        return $array;
    }

    public function table(int $group = 0, array &$array = []): array
    {
        foreach ($this->values() as $key => $value) {
            $parts = $this->getOidParts($key);

            // move the oid name to the correct depth
            array_splice($parts, $group, 0, array_shift($parts));

            // merge the parts into an array, creating keys if they don't exist
            $tmp = &$array;
            foreach ($parts as $part) {
                $key = trim((string) $part, '"');
                $tmp = &$tmp[$key];
            }
            $tmp = $value; // assign the value as the leaf
        }

        return Arr::wrap($array); // if no parts, wrap the value
    }

    /**
     * Map an snmp table with callback. If invalid data is encountered, an empty collection is returned.
     * Variables passed to the callback will be an array of row values followed by each individual index.
     */
    public function mapTable(callable $callback): Collection
    {
        if (! $this->isValid(true)) {
            return new Collection;
        }

        $data = [];
        foreach ($this->values() as $key => $value) {
            $parts = $this->getOidParts($key);
            $oid = array_shift($parts);
            $data[implode('][', $parts)][$oid] = $value;
        }

        $return = new Collection;
        foreach ($data as $index => $values) {
            $return->push(call_user_func($callback, $values, ...explode('][', $index)));
        }

        return $return;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function append(SnmpResponse $response): SnmpResponse
    {
        $newResponse = new SnmpResponse(
            // only the values, a failed oid (No Such Instance) must not invalidate the others, like the cli output
            array_merge($this->values(), $response->values()),
            $this->stderr . $response->stderr,
            $this->exitCode ?: $response->exitCode,
            $response->command ?: $this->command,
            $this->options ?? $response->options,
        );

        $newResponse->errorMessage = $this->errorMessage ?: $response->errorMessage;

        return $newResponse;
    }

    public function __toString(): string
    {
        return $this->raw();
    }

    private function getOidParts(string $key): array
    {
        // table
        if (str_contains($key, '[')) {
            preg_match_all('/([^[\]]+)/', $key, $parts);

            return $parts[1]; // get all group 1 matches
        }

        // regular oid
        return explode('.', $key);
    }

    public function __sleep()
    {
        return ['values', 'rawValues', 'badString', 'exitCode', 'stderr', 'command', 'errorMessage'];
    }
}
