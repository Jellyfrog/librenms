<?php

/**
 * SnmpTable.php
 *
 * A walked SNMP table: rows keyed by the numeric index, each with the decoded 'index' and native column values.
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
 */

namespace LibreNMS\Data\Source\Snmp;

readonly class SnmpTable
{
    /**
     * @param  array<string, array<string, mixed>>  $rows
     */
    public function __construct(
        private array $rows = [],
        public int $errorCode = 0,
        public string $errorMessage = '',
    ) {
    }

    /**
     * Keep the rows received before the request failed
     */
    public static function fromException(\SNMPException $e): self
    {
        return new self($e->getPartialResult() ?? [], $e->getCode(), $e->getMessage()); /** @phpstan-ignore method.notFound */
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    public function isValid(): bool
    {
        return $this->errorCode === 0 && $this->rows !== [];
    }
}
