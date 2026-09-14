<?php

/**
 * MaintenanceRun.php
 *
 * Runs the registered maintenance jobs for a cadence, one at a time.
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
use App\Jobs\Maintenance\MaintenanceJob;
use App\Maintenance\TaskRegistry;
use App\Models\Eventlog;
use LibreNMS\Enum\Severity;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

use function Illuminate\Support\php_binary;

/**
 * Stands in for a queue worker until LibreNMS ships one.
 *
 * Each job in the cadence is run in its own artisan process, one after the
 * other, through maintenance:run-task. A separate process is what makes the
 * jobs independent: an out of memory condition or a fatal error takes down
 * only that job, where catching Throwable would not help since neither is
 * catchable. It is also how a job that hangs gets killed, since a job's
 * timeout is enforced by a worker and there is none.
 *
 * daily.sh had both properties too, by running each cleanup as its own php
 * process and discarding every exit code. Here they are deliberate.
 *
 * With a worker this command is deleted and the registry's lists are
 * dispatched as a batch; the jobs do not change.
 */
class MaintenanceRun extends LnmsCommand
{
    protected $name = 'maintenance:run';

    /**
     * The part of every job's command line that does not change between jobs.
     *
     * @var array<int, string>
     */
    private array $commandPrefix = [];

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

        $timeout = $this->option('timeout');
        if ($timeout !== null && (! is_numeric($timeout) || (int) $timeout < 1)) {
            $this->error(trans('commands.maintenance:run.bad_timeout'));

            return 1;
        }
        $timeoutOverride = $timeout === null ? null : (int) $timeout;

        $jobs = $this->filterJobs($registry->tasks($cadence));

        if (empty($jobs)) {
            $this->line(trans('commands.maintenance:run.no_tasks', ['cadence' => $cadence]));

            return 0;
        }

        foreach ($jobs as $job) {
            if (! is_subclass_of($job, MaintenanceJob::class)) {
                $this->error(trans('commands.maintenance:run.not_a_task', ['job' => $job]));

                return 1;
            }
        }

        $this->commandPrefix = [
            php_binary(),
            base_path('artisan'),
            '--no-interaction',
            '--no-ansi',
            ...array_filter([$this->subprocessVerbosity()]),
        ];

        $failed = 0;
        foreach ($jobs as $job) {
            // the job knows how long it may run; the option overrides it
            if (! $this->runJob($job, $timeoutOverride ?? (new $job)->timeout)) {
                $failed++;
            }
        }

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Run one job as its own artisan process.
     *
     * Nothing may escape this method: the whole point of the chain is that the
     * jobs after a broken one still get their turn.
     *
     * @param  class-string<MaintenanceJob>  $job
     */
    private function runJob(string $job, int $timeout): bool
    {
        $name = class_basename($job);
        $started = microtime(true);

        try {
            $process = new Process([...$this->commandPrefix, 'maintenance:run-task', $job]);
            $process->setTimeout($timeout);
            // The child's output is streamed straight through and never read back,
            // so do not let Process keep a second copy of it as well.
            $process->disableOutput();
            $process->run(function ($type, $buffer): void {
                // Raw, because this is not console markup: a hostname or SNMP value
                // in angle brackets would otherwise be swallowed as a style tag.
                $this->output->write($buffer, false, OutputInterface::OUTPUT_RAW);
            });

            if ($process->isSuccessful()) {
                $this->line(trans('commands.maintenance:run.task_finished', [
                    'task' => $name,
                    'duration' => $this->elapsed($started),
                ]));

                return true;
            }

            $code = (int) $process->getExitCode();

            // Exit code 1 means the job failed and recorded that itself. Anything
            // else means the process died before or outside the job, and nothing
            // has been recorded yet. See MaintenanceRunTask.
            return $this->jobFailed($name, trans('commands.maintenance:run.task_failed', [
                'task' => $name,
                'code' => $code,
                'duration' => $this->elapsed($started),
            ]), record: $code !== 1);
        } catch (ProcessTimedOutException) {
            return $this->jobFailed($name, trans('commands.maintenance:run.task_timed_out', [
                'task' => $name,
                'timeout' => $timeout,
            ]), record: true);
        } catch (\Throwable $e) {
            return $this->jobFailed($name, trans('commands.maintenance:run.task_errored', [
                'task' => $name,
                'message' => $e->getMessage(),
            ]), record: true);
        }
    }

    /**
     * Report a failed job to the console, and to the eventlog if the job could
     * not have done so itself, then carry on.
     */
    private function jobFailed(string $name, string $message, bool $record): bool
    {
        $this->error($message);

        if (! $record) {
            return false;
        }

        // best effort: a broken database should not stop the remaining jobs
        try {
            Eventlog::log($message, null, 'maintenance', Severity::Error);
        } catch (\Throwable $e) {
            $this->error(trans('commands.maintenance:run.eventlog_failed', [
                'task' => $name,
                'message' => $e->getMessage(),
            ]));
        }

        return false;
    }

    private function subprocessVerbosity(): ?string
    {
        $verbosity = $this->output->getVerbosity();

        return match (true) {
            $verbosity >= OutputInterface::VERBOSITY_DEBUG => '-vvv',
            $verbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE => '-vv',
            $verbosity >= OutputInterface::VERBOSITY_VERBOSE => '-v',
            default => null,
        };
    }

    /**
     * Apply --only and --except. A job may be named by its class name with or
     * without the namespace, so CleanupSyslog is enough at a prompt.
     *
     * @param  array<int, class-string<MaintenanceJob>>  $jobs
     * @return array<int, class-string<MaintenanceJob>>
     */
    private function filterJobs(array $jobs): array
    {
        $named = fn (string $job, array $names): bool => in_array($job, $names, true)
            || in_array(class_basename($job), $names, true);

        if ($only = $this->commaSeparatedOption('only')) {
            $jobs = array_filter($jobs, fn (string $job) => $named($job, $only));
        }

        if ($except = $this->commaSeparatedOption('except')) {
            $jobs = array_filter($jobs, fn (string $job) => ! $named($job, $except));
        }

        return array_values($jobs);
    }

    private function elapsed(float $started): string
    {
        return number_format(microtime(true) - $started, 1);
    }
}
