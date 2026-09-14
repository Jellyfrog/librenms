<?php

/*
 * MaintenanceRunTest.php
 *
 * Tests the maintenance job chain runner.
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

use App\Maintenance\TaskRegistry;
use App\Models\Eventlog;
use LibreNMS\Tests\InMemoryDbTestCase;
use LibreNMS\Tests\Mocks\Maintenance\ExitingJob;
use LibreNMS\Tests\Mocks\Maintenance\FailingJob;
use LibreNMS\Tests\Mocks\Maintenance\PassingJob;
use LibreNMS\Tests\Mocks\Maintenance\SleepingJob;

/**
 * Each job runs in a child process that boots on its own, so the jobs used
 * here are real classes under tests/ rather than anything defined in the test:
 * a closure or an anonymous class would not exist in the child.
 *
 * For the same reason the child cannot see this test's database. What a job
 * records in failed() is covered in MaintenanceJobTest; here the eventlog
 * shows only what the runner itself recorded.
 */
final class MaintenanceRunTest extends InMemoryDbTestCase
{
    /**
     * @param  array<string, array<int, class-string>>  $tasks
     */
    private function registerTasks(array $tasks): void
    {
        $this->app->instance(TaskRegistry::class, new TaskRegistry($tasks));
    }

    private function reportedByRunner(): int
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
        $this->registerTasks(['daily' => [PassingJob::class]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily', '--timeout' => '0'])
            ->expectsOutputToContain(trans('commands.maintenance:run.bad_timeout'))
            ->assertExitCode(1);
    }

    public function testARegistryEntryThatIsNotAJobIsRefusedBeforeAnythingRuns(): void
    {
        $this->registerTasks(['daily' => [\stdClass::class, PassingJob::class]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily'])
            ->expectsOutputToContain(trans('commands.maintenance:run.not_a_task', ['job' => \stdClass::class]))
            ->assertExitCode(1);
    }

    public function testAPassingJobIsReported(): void
    {
        $this->registerTasks(['daily' => [PassingJob::class]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily'])
            ->expectsOutputToContain('Finished PassingJob')
            ->assertExitCode(0);

        $this->assertSame(0, $this->reportedByRunner());
    }

    /**
     * The property the whole design exists for: a job that fails must not stop
     * the ones after it. daily.sh got this by discarding every exit code, so it
     * is the one behaviour a port has to keep.
     */
    public function testAFailingJobDoesNotStopTheChain(): void
    {
        $this->registerTasks(['daily' => [FailingJob::class, PassingJob::class]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily'])
            ->expectsOutputToContain('The maintenance task FailingJob exited with code 1')
            ->expectsOutputToContain('Finished PassingJob')
            ->assertExitCode(1);

        // exit code 1 means the job recorded its own failure, so the runner
        // must not record it again
        $this->assertSame(0, $this->reportedByRunner());
    }

    /**
     * A job that dies -- killed, out of memory, a fatal error -- never reaches
     * failed(), so recording it is the runner's job.
     */
    public function testAJobThatDiesIsRecordedByTheRunner(): void
    {
        $this->registerTasks(['daily' => [ExitingJob::class, PassingJob::class]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily'])
            ->expectsOutputToContain('The maintenance task ExitingJob exited with code 3')
            ->expectsOutputToContain('Finished PassingJob')
            ->assertExitCode(1);

        $this->assertSame(1, $this->reportedByRunner());
    }

    public function testOnlyOptionAcceptsAShortClassName(): void
    {
        $this->registerTasks(['daily' => [FailingJob::class, PassingJob::class]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily', '--only' => 'PassingJob'])
            ->expectsOutputToContain('Finished PassingJob')
            ->assertExitCode(0);
    }

    public function testExceptOptionAcceptsAFullClassName(): void
    {
        $this->registerTasks(['daily' => [FailingJob::class, PassingJob::class]]);

        $this->artisan('maintenance:run', ['cadence' => 'daily', '--except' => FailingJob::class])
            ->expectsOutputToContain('Finished PassingJob')
            ->assertExitCode(0);
    }

    /**
     * The timeout is what bounds a run, and so what keeps runs from overlapping.
     * A hung job must be killed and the chain must carry on past it.
     */
    public function testAHungJobIsKilledAndTheChainContinues(): void
    {
        $this->registerTasks(['daily' => [SleepingJob::class, PassingJob::class]]);

        $started = microtime(true);

        $this->artisan('maintenance:run', ['cadence' => 'daily', '--timeout' => '1'])
            ->expectsOutputToContain('Finished PassingJob')
            ->assertExitCode(1);

        $this->assertLessThan(20, microtime(true) - $started,
            'the hung job should have been killed at its timeout, not waited out');

        $this->assertSame(1, $this->reportedByRunner(),
            'a killed job cannot report itself, so the runner must');
    }
}
