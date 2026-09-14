<?php

/*
 * MaintenanceJobTest.php
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

namespace LibreNMS\Tests\Feature\Maintenance;

use App\Jobs\Maintenance\FetchRss;
use App\Jobs\Maintenance\MaintenanceJob;
use App\Maintenance\TaskResult;
use App\Models\Eventlog;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use LibreNMS\Tests\InMemoryDbTestCase;
use RuntimeException;

final class MaintenanceJobTest extends InMemoryDbTestCase
{
    /**
     * Maintenance work is heavy on the database, so only one task may run at a
     * time. A batch would run them in parallel, so this shared lock is the only
     * thing that serialises them once there are workers.
     */
    public function testDifferentMaintenanceJobsBlockEachOther(): void
    {
        $one = new FetchRss;
        $another = new ReportingJob(TaskResult::make());

        $this->assertCount(1, $this->overlapMiddlewareOf($one),
            'a maintenance job should guard against overlapping');

        // Without shared(), the lock key includes the job class name, so two
        // different tasks would happily run at the same time. Comparing the
        // resolved keys is what actually proves they serialise against each
        // other, rather than just that a flag is set.
        $this->assertSame(
            $this->overlapMiddlewareOf($one)[0]->getLockKey($one),
            $this->overlapMiddlewareOf($another)[0]->getLockKey($another),
            'all maintenance tasks should contend for one lock, not one per class'
        );
    }

    /**
     * Released jobs must come back, not be dropped: a task that has to wait its
     * turn should still run later rather than be skipped for the day.
     */
    public function testAWaitingJobIsReleasedRatherThanDiscarded(): void
    {
        $overlap = $this->overlapMiddlewareOf(new FetchRss)[0];

        $this->assertGreaterThan(0, $overlap->releaseAfter,
            'a blocked maintenance task should be released back, not dropped');
    }

    /**
     * The lock has to outlive the task it guards, or a long task loses its own
     * lock partway through and another task starts alongside it.
     */
    public function testTheOverlapLockOutlivesTheTaskTimeout(): void
    {
        $job = new FetchRss;

        $this->assertGreaterThan($job->timeout, $this->overlapMiddlewareOf($job)[0]->expiresAfter);
    }

    public function testAFailedResultThrowsSoTheQueueRecordsIt(): void
    {
        $job = new ReportingJob(TaskResult::make()->error('it broke'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('it broke');

        $job->handle();
    }

    /**
     * The failure has to be recorded by the task, not by whatever dispatched
     * it, or it is lost the moment a worker replaces the chain runner. This
     * also proves the sync driver reaches failed(), which the runner relies on.
     */
    public function testAFailedJobIsRecordedInTheEventlogUnderTheSyncDriver(): void
    {
        try {
            ReportingJob::dispatchSync(TaskResult::make()->error('it broke'));
            $this->fail('the failure should still propagate after being recorded');
        } catch (RuntimeException) {
            // expected
        }

        $entries = Eventlog::where('type', 'maintenance')->pluck('message');

        $this->assertCount(1, $entries, 'one failure, one eventlog entry');
        $this->assertStringContainsString('it broke', $entries->first());
        $this->assertStringContainsString('ReportingJob', $entries->first(),
            'the entry should say which task failed');
    }

    public function testASuccessfulResultDoesNotThrow(): void
    {
        // a warning is not a failure: a task that is switched off, or that
        // found nothing to do, still succeeded
        $job = new ReportingJob(TaskResult::make()->warning('nothing to do'));

        $job->handle();

        $this->assertTrue(true);
    }

    /**
     * @return array<int, WithoutOverlapping>
     */
    private function overlapMiddlewareOf(MaintenanceJob $job): array
    {
        return array_values(array_filter(
            $job->middleware(),
            fn ($middleware) => $middleware instanceof WithoutOverlapping
        ));
    }
}

class ReportingJob extends MaintenanceJob
{
    public function __construct(private readonly TaskResult $result)
    {
    }

    public function handle(): void
    {
        $this->report($this->result);
    }
}
