<?php

/*
 * MaintenanceCleanupRrdTest.php
 *
 * -Description-
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2026 LibreNMS
 * @author     LibreNMS Contributors
 */

namespace LibreNMS\Tests\Feature\Commands;

use App\Facades\LibrenmsConfig;
use Illuminate\Support\Facades\File;
use LibreNMS\Tests\TestCase;

final class MaintenanceCleanupRrdTest extends TestCase
{
    private string $rrdDir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->rrdDir = sys_get_temp_dir() . '/librenms-rrd-purge-test-' . bin2hex(random_bytes(8));
        File::makeDirectory($this->rrdDir, 0755, true);
        LibrenmsConfig::set('rrd_dir', $this->rrdDir);
    }

    protected function tearDown(): void
    {
        if ($this->rrdDir !== '' && is_dir($this->rrdDir)) {
            File::deleteDirectory($this->rrdDir);
        }

        parent::tearDown();
    }

    public function testOldFilesDeletedRecentFilesKept(): void
    {
        $old = $this->makeFile('old.rrd', 40);
        $recent = $this->makeFile('recent.rrd', 2);

        LibrenmsConfig::set('rrd_purge', 30);

        $this->artisan('maintenance:cleanup-rrd')->assertExitCode(0);

        $this->assertFileDoesNotExist($old);
        $this->assertFileExists($recent);
    }

    public function testRecursesIntoSubdirectoriesAndKeepsDirectories(): void
    {
        $nestedOld = $this->makeFile('device1/nested/old.rrd', 40);
        $nestedRecent = $this->makeFile('device1/nested/recent.rrd', 1);

        LibrenmsConfig::set('rrd_purge', 30);

        $this->artisan('maintenance:cleanup-rrd')->assertExitCode(0);

        $this->assertFileDoesNotExist($nestedOld);
        $this->assertFileExists($nestedRecent);

        // directories are never removed, even when they end up empty
        $this->assertDirectoryExists($this->rrdDir . '/device1/nested');
        $this->assertDirectoryExists($this->rrdDir . '/device1');
    }

    public function testEmptiedDirectoryIsNotRemoved(): void
    {
        $old = $this->makeFile('emptyme/old.rrd', 90);

        LibrenmsConfig::set('rrd_purge', 7);

        $this->artisan('maintenance:cleanup-rrd')->assertExitCode(0);

        $this->assertFileDoesNotExist($old);
        $this->assertDirectoryExists($this->rrdDir . '/emptyme');
    }

    public function testGitignoreIsNeverDeleted(): void
    {
        $gitignore = $this->makeFile('.gitignore', 400);
        $nestedGitignore = $this->makeFile('sub/.gitignore', 400);
        $dotfile = $this->makeFile('sub/.hidden.rrd', 400);
        $sibling = $this->makeFile('sub/old.rrd', 400);

        LibrenmsConfig::set('rrd_purge', 30);

        $this->artisan('maintenance:cleanup-rrd')->assertExitCode(0);

        $this->assertFileExists($gitignore);
        $this->assertFileExists($nestedGitignore);
        // dot files are purged, find does not skip them, and a .gitignore in the
        // tree must not cause its siblings to be skipped
        $this->assertFileDoesNotExist($dotfile);
        $this->assertFileDoesNotExist($sibling);
    }

    public function testDisabledWhenPurgeIsZero(): void
    {
        $old = $this->makeFile('old.rrd', 400);

        LibrenmsConfig::set('rrd_purge', 0);

        $this->artisan('maintenance:cleanup-rrd')->assertExitCode(0);

        $this->assertFileExists($old);
    }

    public function testDisabledWhenPurgeIsUnset(): void
    {
        $old = $this->makeFile('old.rrd', 400);

        LibrenmsConfig::forget('rrd_purge');

        $this->artisan('maintenance:cleanup-rrd')->assertExitCode(0);

        $this->assertFileExists($old);
    }

    public function testDisabledWhenPurgeIsNotNumeric(): void
    {
        $old = $this->makeFile('old.rrd', 400);

        LibrenmsConfig::set('rrd_purge', 'yes please');

        $this->artisan('maintenance:cleanup-rrd')->assertExitCode(0);

        $this->assertFileExists($old);
    }

    public function testDaysArgumentOverridesConfig(): void
    {
        $old = $this->makeFile('old.rrd', 10);
        $recent = $this->makeFile('recent.rrd', 2);

        LibrenmsConfig::set('rrd_purge', 0); // disabled by config, argument wins

        $this->artisan('maintenance:cleanup-rrd', ['days' => 5])->assertExitCode(0);

        $this->assertFileDoesNotExist($old);
        $this->assertFileExists($recent);
    }

    public function testNonNumericDaysArgumentFails(): void
    {
        $old = $this->makeFile('old.rrd', 400);

        LibrenmsConfig::set('rrd_purge', 30);

        $this->artisan('maintenance:cleanup-rrd', ['days' => 'lots'])->assertExitCode(1);

        $this->assertFileExists($old);
    }

    public function testFileListIsOnlyPrintedWhenVerbose(): void
    {
        $this->makeFile('old.rrd', 400);
        LibrenmsConfig::set('rrd_purge', 30);

        $this->artisan('maintenance:cleanup-rrd')
            ->doesntExpectOutputToContain('old.rrd')
            ->expectsOutputToContain('(1 files)')
            ->assertExitCode(0);

        $this->makeFile('old.rrd', 400);

        $this->artisan('maintenance:cleanup-rrd', ['-v' => true])
            ->expectsOutputToContain('old.rrd')
            ->assertExitCode(0);
    }

    public function testUndeletableFileIsReported(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root ignores file permissions');
        }

        $locked = $this->makeFile('locked/old.rrd', 400);
        chmod($this->rrdDir . '/locked', 0500); // readable, not writable

        LibrenmsConfig::set('rrd_purge', 30);

        $this->artisan('maintenance:cleanup-rrd')
            ->expectsOutputToContain('Failed to purge')
            ->assertExitCode(0);

        $this->assertFileExists($locked);

        chmod($this->rrdDir . '/locked', 0755); // allow tearDown to clean up
    }

    public function testMissingRrdDirDoesNotThrow(): void
    {
        LibrenmsConfig::set('rrd_dir', $this->rrdDir . '/does-not-exist');
        LibrenmsConfig::set('rrd_purge', 30);

        $this->artisan('maintenance:cleanup-rrd')->assertExitCode(0);
    }

    /**
     * Create a file $daysOld days old (relative to now) and return its full path.
     */
    private function makeFile(string $relativePath, int $daysOld): string
    {
        $path = $this->rrdDir . '/' . $relativePath;
        $dir = dirname($path);

        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($path, 'rrd data');
        touch($path, time() - ($daysOld * 86400) - 60);

        return $path;
    }
}
