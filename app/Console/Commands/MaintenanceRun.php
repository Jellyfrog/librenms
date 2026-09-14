<?php

/**
 * MaintenanceRun.php
 *
 * Runs the registered maintenance tasks for a cadence, one at a time.
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

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Maintenance\TaskRegistry;
use App\Models\Eventlog;
use LibreNMS\Enum\Severity;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Replaces the cleanup chain daily.sh used to run, which looped over a list of
 * options and ran each one as its own php process. That gave two properties
 * worth keeping: tasks run one at a time, and a task that dies cannot stop the
 * ones after it. daily.sh got the second one by accident, by discarding every
 * exit code; here it is deliberate.
 *
 * Each task is run as a separate artisan process so that an out of memory
 * condition or a fatal error takes down only that task. Catching Throwable
 * would not be enough, since neither of those is catchable.
 *
 * Why not a queue: LibreNMS does not ship a queue runner. Once it does, the
 * registry entries should become queued jobs and this command can be deleted --
 * a worker gives sequencing, failure isolation, timeouts and retries natively.
 * See App\Maintenance\TaskRegistry for the rest of that note.
 */
class MaintenanceRun extends LnmsCommand
{
    protected $name = 'maintenance:run';

    public function __construct()
    {
        parent::__construct();

        $this->addArgument('cadence', InputArgument::REQUIRED);
        $this->addOption('only', null, InputOption::VALUE_REQUIRED);
        $this->addOption('except', null, InputOption::VALUE_REQUIRED);
        $this->addOption('timeout', null, InputOption::VALUE_REQUIRED);
    }

    public function handle(TaskRegistry $registry): int
    {
        $cadence = (string) $this->argument('cadence');

        if (! $registry->has($cadence)) {
            $this->error(trans('commands.maintenance:run.unknown_cadence', [
                'cadence' => $cadence,
                'cadences' => implode(', ', $registry->cadences()),
            ]));

            return 1;
        }

        $timeoutOverride = $this->timeoutOverride();
        if ($timeoutOverride === false) {
            return 1;
        }

        $tasks = $this->filterTasks($registry->tasks($cadence));

        if (empty($tasks)) {
            $this->line(trans('commands.maintenance:run.no_tasks', ['cadence' => $cadence]));

            return 0;
        }

        $failed = 0;
        foreach ($tasks as $task => $timeout) {
            if (! $this->runTask($task, $timeoutOverride ?? $timeout)) {
                $failed++;
            }
        }

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Run one task as its own artisan process.
     *
     * Nothing may escape this method: the whole point of the chain is that the
     * tasks after a broken one still get their turn.
     */
    private function runTask(string $task, int $timeout): bool
    {
        $started = microtime(true);

        try {
            $process = new Process($this->buildCommand($task));
            $process->setTimeout($timeout);
            $process->run(function ($type, $buffer): void {
                $this->output->write($buffer);
            });

            if ($process->isSuccessful()) {
                $this->line(trans('commands.maintenance:run.task_finished', [
                    'task' => $task,
                    'duration' => $this->elapsed($started),
                ]));

                return true;
            }

            return $this->taskFailed($task, trans('commands.maintenance:run.task_failed', [
                'task' => $task,
                'code' => (int) $process->getExitCode(),
                'duration' => $this->elapsed($started),
            ]));
        } catch (ProcessTimedOutException) {
            return $this->taskFailed($task, trans('commands.maintenance:run.task_timed_out', [
                'task' => $task,
                'timeout' => $timeout,
            ]));
        } catch (\Throwable $e) {
            return $this->taskFailed($task, trans('commands.maintenance:run.task_errored', [
                'task' => $task,
                'message' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Report a failed task to the console and the eventlog, then carry on.
     */
    private function taskFailed(string $task, string $message): bool
    {
        $this->error($message);

        // best effort: a broken database should not stop the remaining tasks
        try {
            Eventlog::log($message, null, 'maintenance', Severity::Error);
        } catch (\Throwable $e) {
            $this->error(trans('commands.maintenance:run.eventlog_failed', [
                'task' => $task,
                'message' => $e->getMessage(),
            ]));
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function buildCommand(string $task): array
    {
        $command = [
            (new PhpExecutableFinder)->find(false) ?: PHP_BINARY,
            base_path('artisan'),
            $task,
            '--no-interaction',
            '--no-ansi',
        ];

        if ($verbosity = $this->subprocessVerbosity()) {
            $command[] = $verbosity;
        }

        return $command;
    }

    private function subprocessVerbosity(): ?string
    {
        return match (true) {
            $this->output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG => '-vvv',
            $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE => '-vv',
            $this->output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE => '-v',
            default => null,
        };
    }

    /**
     * @param  array<string, int>  $tasks
     * @return array<string, int>
     */
    private function filterTasks(array $tasks): array
    {
        if ($only = $this->commaSeparatedOption('only')) {
            $tasks = array_intersect_key($tasks, array_flip($only));
        }

        if ($except = $this->commaSeparatedOption('except')) {
            $tasks = array_diff_key($tasks, array_flip($except));
        }

        return $tasks;
    }

    /**
     * @return int|null|false null when unset, false when invalid
     */
    private function timeoutOverride(): int|null|false
    {
        $timeout = $this->option('timeout');

        if ($timeout === null) {
            return null;
        }

        if (! is_numeric($timeout) || (int) $timeout <= 0) {
            $this->error(trans('commands.maintenance:run.bad_timeout'));

            return false;
        }

        return (int) $timeout;
    }

    private function elapsed(float $started): string
    {
        return number_format(microtime(true) - $started, 1);
    }
}
