<?php

/**
 * RancidProviderTest.php
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 LibreNMS
 */

uses(\LibreNMS\Tests\TestCase::class);
use App\ConfigBackup\Providers\RancidProvider;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Eventlog;
use LibreNMS\Enum\Severity;
use LibreNMS\Interfaces\ConfigBackupProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;


beforeEach(function () {
    $this->repo = sys_get_temp_dir() . '/rancid-test-' . uniqid();
    $this->configs = $this->repo . '/configs';
    mkdir($this->configs, 0777, true);
    LibrenmsConfig::set('rancid_configs', [$this->configs]);
});

afterEach(function () {
    if ($this->repo && is_dir($this->repo)) {
        new Process(['rm', '-rf', $this->repo])->run();
    }
});

/**
 * @param  list<string>  $args
 */
function git(array $args): void
{
    new Process(array_merge(['git'], $args), $this->repo)->mustRun();
}

/**
 * Commit a new revision of the device config to the test git repo.
 */
function commitConfig(string $content, string $hostname = 'router.example.com'): void
{
    file_put_contents($this->configs . '/' . $hostname, $content);
    git(['add', '.']);
    git(['commit', '-q', '-m', 'update']);
}

/**
 * Bare clone of the test git repo, as RANCID pushes to.
 */
function bareClone(string $path): void
{
    mkdir(dirname($path), 0777, true);
    new Process(['git', 'clone', '-q', '--bare', $this->repo, $path])->mustRun();
    LibrenmsConfig::set('rancid_repo_type', 'git-bare');
}

/**
 * Build an svn repo and checkout laid out like RANCID ($repo/SVN, $repo/core/configs)
 * with the given revisions of the device config committed in order.
 *
 * @param  list<string>  $revisions
 */
function initSvnRepo(array $revisions): void
{
    $svn = LibrenmsConfig::locateBinary('svn');
    if (! is_executable($svn) || ! is_executable(dirname($svn) . '/svnadmin')) {
        $this->markTestSkipped('svn is not installed');
    }

    LibrenmsConfig::set('rancid_repo_type', 'svn');
    $url = 'file://' . $this->repo . '/SVN';
    new Process([dirname($svn) . '/svnadmin', 'create', $this->repo . '/SVN'])->mustRun();
    new Process([$svn, 'mkdir', '-q', '-m', 'init', "$url/core", "$url/core/configs"])->mustRun();
    new Process([$svn, 'checkout', '-q', "$url/core", $this->repo . '/core'])->mustRun();

    $this->configs = $this->repo . '/core/configs';
    LibrenmsConfig::set('rancid_configs', [$this->configs]);
    foreach ($revisions as $index => $content) {
        file_put_contents($this->configs . '/router.example.com', $content);
        if ($index === 0) {
            new Process([$svn, 'add', '-q', 'router.example.com'], $this->configs)->mustRun();
        }
        new Process([$svn, 'commit', '-q', '-m', "v$index"], $this->configs)->mustRun();
    }
    new Process([$svn, 'update', '-q'], $this->configs)->mustRun();
}

function makeDevice(string $hostname = 'router.example.com', int $id = 42): Device
{
    $device = new Device(['hostname' => $hostname]);
    $device->device_id = $id;

    return $device;
}

/**
 * Build a git repo with two committed revisions of the device config.
 */
function initGitRepo(string $hostname = 'router.example.com'): void
{
    LibrenmsConfig::set('rancid_repo_type', 'git');
    git(['init', '-q']);
    git(['config', 'user.email', 'test@example.com']);
    git(['config', 'user.name', 'Test']);

    file_put_contents($this->configs . '/' . $hostname, "hostname router\nntp server 1.1.1.1\nend\n");
    git(['add', '.']);
    git(['commit', '-q', '-m', 'v1']);

    file_put_contents($this->configs . '/' . $hostname, "hostname router\nntp server 2.2.2.2\nend\n");
    git(['commit', '-qam', 'v2']);
}

test('is configured', function () {
    expect(RancidProvider::isConfigured())->toBeTrue();

    LibrenmsConfig::set('rancid_configs', []);
    expect(RancidProvider::isConfigured())->toBeFalse();
});

test('supports device only when config file exists', function () {
    initGitRepo();
    $provider = resolve(RancidProvider::class);

    expect($provider->supportsDevice(makeDevice()))->toBeTrue();
    expect($provider->supportsDevice(makeDevice('unknown.example.com', 43)))->toBeFalse();
});

test('backups lists revisions newest first', function () {
    initGitRepo();
    $list = (resolve(RancidProvider::class))->backups(makeDevice());

    expect($list['total'])->toBe(2);
    expect($list['totalPages'])->toBe(1);
    expect($list['backups'][0]['id'])->toMatch('/^[0-9a-f]{40}$/');
    expect($list['backups'][0]['date'])->toBeInt();
    expect($list['backups'][0]['type'])->toBe('TEXT');
    expect($list['backups'][0]['date'])->toBeGreaterThanOrEqual($list['backups'][1]['date']);
});

test('latest returns newest content', function () {
    initGitRepo();
    $latest = (resolve(RancidProvider::class))->latest(makeDevice());

    $this->assertStringContainsString('ntp server 2.2.2.2', $latest['content']);
});

test('content fetches specific revision', function () {
    initGitRepo();
    $provider = resolve(RancidProvider::class);

    $backups = $provider->backups(makeDevice())['backups'];
    $oldest = end($backups)['id'];

    $this->assertStringContainsString('ntp server 1.1.1.1', $provider->content(makeDevice(), $oldest));
});

test('content rejects non hex id', function () {
    initGitRepo();
    $provider = resolve(RancidProvider::class);

    expect($provider->content(makeDevice(), 'not-a-hash'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
});

test('diff produces groups', function () {
    initGitRepo();
    $provider = resolve(RancidProvider::class);

    $backups = $provider->backups(makeDevice())['backups'];
    $new = $backups[0]['id'];
    $old = end($backups)['id'];

    $groups = $provider->diff(makeDevice(), $old, $new);

    $deleted = array_values(array_filter($groups, fn ($g) => $g['type'] === 'DELETED'));
    $inserted = array_values(array_filter($groups, fn ($g) => $g['type'] === 'INSERTED'));

    expect($deleted)->not->toBeEmpty();
    expect($inserted)->not->toBeEmpty();
    $this->assertStringContainsString('1.1.1.1', $deleted[0]['original'][0]['text']);
    $this->assertStringContainsString('2.2.2.2', $inserted[0]['revised'][0]['text']);
});

test('diff rejects non hex ids', function () {
    initGitRepo();
    $provider = resolve(RancidProvider::class);

    expect($provider->diff(makeDevice(), 'zzz', 'abc'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
});

test('device not found', function () {
    initGitRepo();
    $provider = resolve(RancidProvider::class);

    expect($provider->backups(makeDevice('unknown.example.com', 99)))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_DEVICE_NOT_FOUND);
});

test('unsupported repo type falls back to current file', function () {
    LibrenmsConfig::set('rancid_repo_type', 'cvs');
    // not git/git-bare/svn
    file_put_contents($this->configs . '/router.example.com', "hostname router\n");
    $provider = resolve(RancidProvider::class);
    $device = makeDevice();

    $list = $provider->backups($device);
    expect($list['total'])->toBe(1);
    expect($list['backups'][0]['id'])->toBe('current');
    expect($list['backups'][0]['date'])->toBeInt();

    $latest = $provider->latest($device);
    expect($latest['id'])->toBe('current');
    expect($latest['content'])->toBe("hostname router\n");
    expect($provider->content($device, 'current'))->toBe("hostname router\n");

    expect($provider->diff($device, 'current', 'current'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
});

test('unreadable repo falls back to current file', function () {
    LibrenmsConfig::set('rancid_repo_type', 'git');
    // but the directory is not a git repo
    file_put_contents($this->configs . '/router.example.com', "hostname router\n");
    $provider = resolve(RancidProvider::class);

    expect($provider->backups(makeDevice())['backups'][0]['id'])->toBe('current');
    expect($provider->latest(makeDevice())['content'])->toBe("hostname router\n");

    expect($provider->diff(makeDevice(), 'abc123', 'def456'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_UNREACHABLE);
});

test('uncommitted file falls back to current file', function () {
    initGitRepo();
    file_put_contents($this->configs . '/new.example.com', "hostname new\n");
    $provider = resolve(RancidProvider::class);

    $list = $provider->backups(makeDevice('new.example.com', 50));
    expect($list['total'])->toBe(1);
    expect($list['backups'][0]['id'])->toBe('current');
});

test('current id only reads device file', function () {
    initGitRepo();
    $provider = resolve(RancidProvider::class);

    // with history available, "current" still maps to this device's own file
    $this->assertStringContainsString('ntp server 2.2.2.2', $provider->content(makeDevice(), 'current'));
});

test('hostname cannot escape configs directory', function () {
    LibrenmsConfig::set('rancid_repo_type', 'cvs');
    file_put_contents($this->repo . '/outside', "secret\n");
    $provider = resolve(RancidProvider::class);

    expect($provider->supportsDevice(makeDevice('../outside')))->toBeFalse();
    expect($provider->supportsDevice(makeDevice('sub/../../outside', 43)))->toBeFalse();
});

test('diff with unknown revision is backup not found', function () {
    initGitRepo();
    $provider = resolve(RancidProvider::class);
    $newest = $provider->backups(makeDevice())['backups'][0]['id'];

    expect($provider->diff(makeDevice(), 'deadbeef', $newest))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
});

test('git bare has no current file fallback', function () {
    initGitRepo();
    $bare = $this->repo . '/rancid.git';
    new Process(['git', 'clone', '-q', '--bare', $this->repo, $bare])->mustRun();
    LibrenmsConfig::set('rancid_repo_type', 'git-bare');
    LibrenmsConfig::set('rancid_configs', [$bare . '/configs']);
    $provider = resolve(RancidProvider::class);

    $this->assertStringContainsString('ntp server 2.2.2.2', $provider->latest(makeDevice())['content']);
    expect($provider->content(makeDevice(), 'current'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
});

test('ignore comments strips hash lines', function () {
    LibrenmsConfig::set('rancid_repo_type', 'git');
    LibrenmsConfig::set('rancid_ignorecomments', true);
    git(['init', '-q']);
    git(['config', 'user.email', 'test@example.com']);
    git(['config', 'user.name', 'Test']);
    file_put_contents($this->configs . '/router.example.com', "keep me\n# drop me\nkeep me too\n");
    git(['add', '.']);
    git(['commit', '-q', '-m', 'v1']);

    $content = (resolve(RancidProvider::class))->latest(makeDevice())['content'];

    $this->assertStringNotContainsString('drop me', $content);
    $this->assertStringContainsString('keep me', $content);
});

/**
 * Replace the eventlog with a mock expecting $times warnings matching $text.
 */
function expectEventlogWarnings(int $times, string $text = ''): void
{
    $eventlog = Mockery::mock(Eventlog::class);
    $eventlog->shouldReceive('_log')
        ->times($times)
        ->withArgs(fn ($message, $device, $type, $severity) => str_contains($message, $text) && $type === 'config' && $severity === Severity::Warning);
    $this->app->instance(Eventlog::class, $eventlog);
}

test('cvs repo type shows current file without warning', function () {
    expectEventlogWarnings(0);
    LibrenmsConfig::set('rancid_repo_type', 'cvs');
    file_put_contents($this->configs . '/router.example.com', "hostname router\n");

    expect((resolve(RancidProvider::class))->backups(makeDevice())['backups'][0]['id'])->toBe('current');
});

test('unreadable repo logs event once per interval', function () {
    expectEventlogWarnings(1, 'RANCID: could not read git history of router.example.com');
    LibrenmsConfig::set('rancid_repo_type', 'git');
    // but the directory is not a git repo
    file_put_contents($this->configs . '/router.example.com', "hostname router\n");

    (resolve(RancidProvider::class))->backups(makeDevice());
    (resolve(RancidProvider::class))->backups(makeDevice());
    // repeated page views do not flood the eventlog
});

test('git bare repo without dot git suffix', function () {
    // rancid-cvs with RCSSYS=git creates bare repos at $BASEDIR/CVS/<group>
    initGitRepo();
    bareClone($this->repo . '/CVS/core');
    LibrenmsConfig::set('rancid_configs', [$this->repo . '/CVS/core/configs/']);

    expect((resolve(RancidProvider::class))->backups(makeDevice())['total'])->toBe(2);
});

test('git bare path with dot git in parent directory', function () {
    initGitRepo();
    bareClone($this->repo . '/.github-mirror/rancid.git');
    LibrenmsConfig::set('rancid_configs', [$this->repo . '/.github-mirror/rancid.git/configs']);

    expect((resolve(RancidProvider::class))->backups(makeDevice())['total'])->toBe(2);
});

test('git bare sibling path', function () {
    // configs at the root of the bare repo, configured without the .git suffix
    LibrenmsConfig::set('rancid_repo_type', 'git');
    git(['init', '-q']);
    git(['config', 'user.email', 'test@example.com']);
    git(['config', 'user.name', 'Test']);
    file_put_contents($this->repo . '/router.example.com', "hostname router\n");
    git(['add', '.']);
    git(['commit', '-q', '-m', 'v1']);
    bareClone($this->repo . '/bare/rancid.git');
    LibrenmsConfig::set('rancid_configs', [$this->repo . '/bare/rancid']);

    expect((resolve(RancidProvider::class))->latest(makeDevice())['content'])->toBe("hostname router\n");
});

test('git bare only matches configs prefix', function () {
    initGitRepo();
    file_put_contents($this->repo . '/other.example.com', "outside configs\n");
    git(['add', '.']);
    git(['commit', '-q', '-m', 'outside']);
    bareClone($this->repo . '/bare/rancid.git');
    LibrenmsConfig::set('rancid_configs', [$this->repo . '/bare/rancid.git/configs']);

    expect((resolve(RancidProvider::class))->supportsDevice(makeDevice('other.example.com', 51)))->toBeFalse();
});

test('git history is paged', function () {
    LibrenmsConfig::set('rancid_repo_type', 'git');
    git(['init', '-q']);

    // fast-import builds 105 commits in one process
    $stream = '';
    for ($i = 1; $i <= 105; $i++) {
        $data = "hostname router\nrevision $i\n";
        $stream .= "commit refs/heads/master\ncommitter Test <test@example.com> " . (1700000000 + $i) . " +0000\ndata 1\n\n"
            . 'M 644 inline configs/router.example.com' . "\ndata " . strlen($data) . "\n$data\n";
    }
    new Process(['git', 'fast-import', '--quiet'], $this->repo, null, $stream)->mustRun();
    git(['symbolic-ref', 'HEAD', 'refs/heads/master']);
    git(['reset', '-q', '--hard']);

    $provider = resolve(RancidProvider::class);
    $first = $provider->backups(makeDevice());
    $second = $provider->backups(makeDevice(), 1);

    expect($first['total'])->toBe(105);
    expect($first['totalPages'])->toBe(2);
    expect($first['backups'])->toHaveCount(100);
    expect($first['backups'][0]['date'])->toBe(1700000105);
    expect($second['backups'])->toHaveCount(5);
    expect($second['page'])->toBe(1);
    expect($second['backups'][4]['date'])->toBe(1700000001);
    expect($provider->backups(makeDevice(), 9)['page'])->toBe(1);
});

test('ignore comments applies to diff', function () {
    LibrenmsConfig::set('rancid_repo_type', 'git');
    LibrenmsConfig::set('rancid_ignorecomments', true);
    git(['init', '-q']);
    git(['config', 'user.email', 'test@example.com']);
    git(['config', 'user.name', 'Test']);
    commitConfig("# uptime 1\nhostname router\n# c\na\nb\nc\nd\ne\nf\ng\nntp server 1.1.1.1\nend\n");
    commitConfig("# uptime 2\nhostname router\n# c\na\nb\nc\nd\ne\nf\ng\nntp server 2.2.2.2\nend\n");
    commitConfig("# uptime 3\nhostname router\n# c\na\nb\nc\nd\ne\nf\ng\nntp server 2.2.2.2\nend\n");

    $provider = resolve(RancidProvider::class);
    $ids = array_column($provider->backups(makeDevice())['backups'], 'id');

    // comment-only change shows as no changes
    expect($provider->diff(makeDevice(), $ids[1], $ids[0]))->toBe([]);

    $groups = $provider->diff(makeDevice(), $ids[2], $ids[1]);
    $texts = array_map(fn ($g) => $g['type'] . ' ' . ($g['original'][0]['line'] ?? $g['revised'][0]['line']) . ' ' . ($g['original'][0]['text'] ?? $g['revised'][0]['text']), $groups);
    expect($texts)->toBe([
        'COMMON 6 e',
        'COMMON 7 f',
        'COMMON 8 g',
        'DELETED 9 ntp server 1.1.1.1',
        'INSERTED 9 ntp server 2.2.2.2',
        'COMMON 10 end',
    ]);

    // line numbers match the filtered content
    $lines = explode("\n", $provider->content(makeDevice(), $ids[1]));
    expect($lines[8])->toBe('ntp server 2.2.2.2');
});

test('svn repo', function () {
    initSvnRepo(["hostname router\nntp server 1.1.1.1\nend\n", "hostname router\nntp server 2.2.2.2\nend\n"]);
    $provider = resolve(RancidProvider::class);
    $device = makeDevice();

    $list = $provider->backups($device);
    expect($list['total'])->toBe(2);
    expect(array_column($list['backups'], 'id'))->toBe(['3', '2']);
    expect($list['backups'][0]['date'])->toBeInt();

    $latest = $provider->latest($device);
    expect($latest['id'])->toBe('3');
    $this->assertStringContainsString('2.2.2.2', $latest['content']);
    $this->assertStringContainsString('1.1.1.1', $provider->content($device, '2'));

    $types = array_column($provider->diff($device, '2', '3'), 'type');
    expect($types)->toBe(['COMMON', 'DELETED', 'INSERTED', 'COMMON']);
})->group('external-dependencies');

test('svn bad revisions', function () {
    initSvnRepo(["hostname router\n", "hostname router2\n"]);
    $provider = resolve(RancidProvider::class);
    $device = makeDevice();

    expect($provider->content($device, 'abc'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
    expect($provider->content($device, '999'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
    expect($provider->diff($device, '2', '999'))->toBeNull();
    expect($provider->lastError())->toBe(ConfigBackupProvider::ERROR_BACKUP_NOT_FOUND);
})->group('external-dependencies');

test('svn ignore comments diff', function () {
    LibrenmsConfig::set('rancid_ignorecomments', true);
    initSvnRepo(["# v1\nhostname router\nntp server 1.1.1.1\n", "# v2\nhostname router\nntp server 2.2.2.2\n"]);
    $provider = resolve(RancidProvider::class);

    $groups = $provider->diff(makeDevice(), '2', '3');
    expect(array_column($groups, 'type'))->toBe(['COMMON', 'DELETED', 'INSERTED']);
    expect($groups[1]['original'][0]['line'])->toBe(2);
})->group('external-dependencies');