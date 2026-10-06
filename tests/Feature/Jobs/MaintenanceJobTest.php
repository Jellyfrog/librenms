<?php

namespace LibreNMS\Tests\Feature\Jobs;

use App\Jobs\MaintenanceJob;
use App\Models\Eventlog;
use App\Models\MaintenanceJobRun;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LibreNMS\Tests\DBTestCase;
use RuntimeException;

final class MaintenanceJobTest extends DBTestCase
{
    use DatabaseTransactions;

    public function testJobIsQueuedOnMaintenanceQueue(): void
    {
        TestSuccessfulMaintenanceJob::dispatch();

        $this->assertSame(1, DB::table('jobs')->where('queue', 'maintenance')->count());
    }

    public function testSuccessfulRunIsRecorded(): void
    {
        TestSuccessfulMaintenanceJob::dispatch();
        $this->work();

        $run = MaintenanceJobRun::where('job', TestSuccessfulMaintenanceJob::class)->sole();
        $this->assertSame(MaintenanceJobRun::STATUS_SUCCESS, $run->status);
        $this->assertNotNull($run->queued_at);
        $this->assertNotNull($run->finished_at);
        $this->assertGreaterThanOrEqual(0, $run->duration_ms);
        $this->assertNull($run->exception);
        $this->assertSame(0, DB::table('jobs')->where('queue', 'maintenance')->count());
    }

    public function testFailedRunIsRecorded(): void
    {
        TestFailingMaintenanceJob::dispatch();
        $this->work();

        $run = MaintenanceJobRun::where('job', TestFailingMaintenanceJob::class)->sole();
        $this->assertSame(MaintenanceJobRun::STATUS_FAILED, $run->status);
        $this->assertSame('maintenance failed', $run->exception);
        $this->assertNotNull($run->duration_ms);
        $this->assertTrue(Eventlog::where('type', 'maintenance')->where('message', 'like', '%maintenance failed%')->exists());
        $this->assertSame(1, DB::table('failed_jobs')->where('queue', 'maintenance')->count());
    }

    public function testDuplicateIsNotQueuedWhileUnique(): void
    {
        TestSuccessfulMaintenanceJob::dispatch();
        TestSuccessfulMaintenanceJob::dispatch();

        $this->assertSame(1, DB::table('jobs')->where('queue', 'maintenance')->count());
    }

    public function testPrunesOldRuns(): void
    {
        MaintenanceJobRun::create(['job' => 'old', 'started_at' => now()->subDays(31), 'status' => MaintenanceJobRun::STATUS_SUCCESS]);
        MaintenanceJobRun::create(['job' => 'new', 'started_at' => now()->subDay(), 'status' => MaintenanceJobRun::STATUS_SUCCESS]);

        Artisan::call('model:prune', ['--model' => [MaintenanceJobRun::class]]);

        $this->assertSame(['new'], MaintenanceJobRun::pluck('job')->all());
    }

    private function work(): void
    {
        Artisan::call('queue:work', ['connection' => 'maintenance', '--queue' => 'maintenance', '--stop-when-empty' => true]);
    }
}

class TestSuccessfulMaintenanceJob extends MaintenanceJob
{
    public function handle(): void
    {
    }
}

class TestFailingMaintenanceJob extends MaintenanceJob
{
    public function handle(): void
    {
        throw new RuntimeException('maintenance failed');
    }
}
