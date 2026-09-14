<?php

/**
 * TaskResult.php
 *
 * What a maintenance task did, without saying how it should be displayed.
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

namespace App\Maintenance;

/**
 * Lets one piece of logic serve both a console command and a queued job.
 *
 * The command renders the messages and maps failed() to an exit code; the job
 * logs them and throws on failure so the queue can record it. Neither concern
 * leaks into the task itself. Same split as App\PerDeviceProcess, which turns a
 * LibreNMS\Polling\Result into console output and an exit code.
 */
class TaskResult
{
    public const INFO = 'info';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    /** @var array<int, array{level: string, text: string}> */
    private array $messages = [];

    private bool $failed = false;

    public static function make(): self
    {
        return new self;
    }

    public function info(string $text): self
    {
        return $this->add(self::INFO, $text);
    }

    /**
     * Something worth saying, but not a failure. A task that is switched off in
     * config, or that found nothing to do, reports this and still succeeds.
     */
    public function warning(string $text): self
    {
        return $this->add(self::WARNING, $text);
    }

    /**
     * Something went wrong. The command exits non-zero and the job throws.
     */
    public function error(string $text): self
    {
        $this->failed = true;

        return $this->add(self::ERROR, $text);
    }

    /**
     * @return array<int, array{level: string, text: string}>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    public function failed(): bool
    {
        return $this->failed;
    }

    /**
     * Every message as one string, for logging or an exception message.
     */
    public function summary(): string
    {
        return implode('; ', array_column($this->messages, 'text'));
    }

    private function add(string $level, string $text): self
    {
        $this->messages[] = ['level' => $level, 'text' => $text];

        return $this;
    }
}
