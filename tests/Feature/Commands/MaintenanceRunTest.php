<?php

/*
 * MaintenanceRunTest.php
 *
 * Tests the maintenance task chain runner.
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

namespace LibreNMS\Tests\Feature\Commands;

use App\Console\Commands\MaintenanceRun;
use App\Facades\LibrenmsConfig;
use App\Maintenance\TaskRegistry;
use App\Models\Eventlog;
use Illuminate\Contracts\Console\Kernel;
use LibreNMS\Tests\InMemoryDbTestCase;
use Symfony\Component\Process\PhpExecutableFinder;

final class MaintenanceRunTest extends InMemoryDbTestCase
{
    /**
     * A real command that succeeds. list:bash-completion is deliberate: it is the
     * only command exempt from the running-user check, so the subprocess behaves
     * the same whether the suite runs as root or as the librenms user.
     */
    private const PASSING_TASK = 'list:bash-completion';

    private const FAILING_TASK = 'bogus:does-not-exist';

    /**
     * @param  array<string, array<string, int>>  $tasks
     */
    private function registerTasks(array $tasks): void
    {
        $this->app->instance(TaskRegistry::class, new TaskRegistry($tasks));
    }

    private function reportedFailures(): int
    {
        return Eventlog::where('type', 'maintenance')->count();
    }

    public function testUnknownCadenceIsRejected(): void
    {
        $this->registerTasks(['daily' => []]);

        $this->artisan('maintenance:run', ['cadence' => 'nonsense'])
            ->expectsOutputToContain(trans('commands.maintenance:run.unknown_cadence', [
                'cadence' => 'nonsense',
                'cadences' => 'daily',
            ]))
            ->assertExitCode(1);
    }

    public function testCadenceWithNoTasksSucceeds(): void
    {
        $this->registerTasks(['daily' => []]);

        $this->artisan('maintenance:run', ['cadence' => 'daily'])
            ->expectsOutputToContain(trans('commands.maintenance:run.no_tasks', ['cadence' => 'daily']))
            ->assertExitCode(0);
    }

    public function testNonPositiveTimeoutIsRejected(): void
    {
        $this->registerTasks(['daily' => [self::PASSING_TASK => 60]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily', '--timeout' => '0'])
            ->expectsOutputToContain(trans('commands.maintenance:run.bad_timeout'))
            ->assertExitCode(1);
    }

    public function testPassingTaskIsReported(): void
    {
        $this->registerTasks(['daily' => [self::PASSING_TASK => 120]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily'])
            ->assertExitCode(0);

        $this->assertSame(0, $this->reportedFailures(),
            'a task that succeeded should not write an eventlog entry');
    }

    /**
     * The property the whole design exists for: a task that dies must not stop
     * the tasks after it. daily.sh got this by discarding every exit code, so it
     * is the one behaviour a port has to keep.
     */
    public function testAFailingTaskDoesNotStopTheChain(): void
    {
        $this->registerTasks(['daily' => [
            self::FAILING_TASK => 120,
            self::PASSING_TASK => 120,
            'bogus:also-does-not-exist' => 120,
        ]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily'])
            ->assertExitCode(1);

        $reported = Eventlog::where('type', 'maintenance')->pluck('message');

        // reaching the third task at all proves the chain did not stop at the first
        $this->assertCount(2, $reported,
            'both failing tasks should be reported, so the chain ran to the end');

        foreach ([self::FAILING_TASK, 'bogus:also-does-not-exist'] as $task) {
            $this->assertTrue($reported->contains(fn ($message) => str_contains($message, $task)),
                "the failure of $task should have been reported");
        }

        // the task between the two broken ones ran, and ran cleanly
        $this->assertFalse($reported->contains(fn ($message) => str_contains($message, self::PASSING_TASK)),
            'the task between the failing ones should have succeeded');
    }

    public function testOnlyOptionLimitsTheChain(): void
    {
        $this->registerTasks(['daily' => [
            self::FAILING_TASK => 120,
            self::PASSING_TASK => 120,
        ]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily', '--only' => self::PASSING_TASK])
            ->assertExitCode(0);

        $this->assertSame(0, $this->reportedFailures(),
            'the failing task should have been filtered out');
    }

    public function testExceptOptionSkipsTasks(): void
    {
        $this->registerTasks(['daily' => [
            self::FAILING_TASK => 120,
            self::PASSING_TASK => 120,
        ]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily', '--except' => self::FAILING_TASK])
            ->assertExitCode(0);

        $this->assertSame(0, $this->reportedFailures());
    }

    /**
     * The auto_discover guard used to live on the schedule entry, so moving the
     * command into the registry would have turned discovery on everywhere it was
     * switched off. The guard has to be in the command itself.
     */
    public function testSslDiscoveryStaysOffWhenAutoDiscoverIsDisabled(): void
    {
        LibrenmsConfig::set('ssl_certificates.auto_discover', false);

        $this->artisan('maintenance:discover-ssl-certificates')
            ->expectsOutputToContain(trans('commands.maintenance:discover-ssl-certificates.disabled'))
            ->assertExitCode(0);
    }

    public function testSslDiscoveryCanBeForcedWhenDisabled(): void
    {
        LibrenmsConfig::set('ssl_certificates.auto_discover', false);

        // no devices exist, so this proves only that the guard was bypassed
        $this->artisan('maintenance:discover-ssl-certificates', ['--force' => true])
            ->expectsOutputToContain(trans('commands.maintenance:discover-ssl-certificates.no_devices'))
            ->assertExitCode(0);
    }

    /**
     * The timeout is what bounds a run, and so what keeps runs from overlapping.
     * A hung task must be killed and the chain must carry on past it.
     */
    public function testHungTaskIsKilledAndTheChainContinues(): void
    {
        $this->registerTasks(['daily' => ['sleeper' => 1, self::PASSING_TASK => 120]]);

        $this->app[Kernel::class]->registerCommand(new SleepingMaintenanceRun);

        $started = microtime(true);

        $this->artisan('maintenance:run-sleeping-test', ['cadence' => 'daily', '--timeout' => '1'])
            ->assertExitCode(1);

        $this->assertLessThan(20, microtime(true) - $started,
            'the hung task should have been killed at its timeout, not waited out');

        $this->assertSame(1, $this->reportedFailures(),
            'the timed out task should be reported, and only it');
    }
}

/**
 * maintenance:run with the subprocess swapped for a command that hangs, so the
 * timeout path can be exercised without depending on a slow real task.
 */
class SleepingMaintenanceRun extends MaintenanceRun
{
    protected $name = 'maintenance:run-sleeping-test';

    protected function buildCommand(string $task): array
    {
        if ($task === 'sleeper') {
            return [(new PhpExecutableFinder)->find(false) ?: PHP_BINARY, '-r', 'sleep(30);'];
        }

        return parent::buildCommand($task);
    }
}
