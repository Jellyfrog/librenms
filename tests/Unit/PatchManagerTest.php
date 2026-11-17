<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Patches\Patch;
use App\Patches\PatchException;
use App\Patches\PatchManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use LibreNMS\Util\Binaries;
use Symfony\Component\Process\Process;


beforeEach(function () {
    $this->tmp = sys_get_temp_dir() . '/lnms-patch-test-' . uniqid();
    $this->upstream = "$this->tmp/upstream";
    $this->local = "$this->tmp/local";
    mkdir($this->upstream, 0777, true);

    git($this->upstream, 'init', '--quiet', '--initial-branch=master');
    write($this->upstream, 'file.txt', "one\ntwo\nthree\nfour\nfive\nsix\nseven\neight\nnine\nten\n");
    write($this->upstream, 'other.txt', "other\n");
    commit($this->upstream, 'initial');

    git($this->tmp, 'clone', '--quiet', $this->upstream, $this->local);

    // serve the upstream test repo like GitHub does
    Http::fake(function ($request) {
        $url = $request->url();

        if (preg_match('#/pulls/(\d+)$#', $url, $matches)) {
            return isset($this->apiResponses[(int) $matches[1]])
                ? Http::response($this->apiResponses[(int) $matches[1]])
                : Http::response(['message' => 'Not Found'], 404);
        }

        if (preg_match('#^https://github.com/librenms/librenms/pull/(\d+)\.diff$#', $url, $matches)) {
            $base = $this->apiResponses[(int) $matches[1]]['base']['ref'] ?? 'master';
            $process = new Process(['git', 'diff', '--find-renames', "$base...refs/pull/$matches[1]/head"], $this->upstream);
            $process->run();

            return $process->isSuccessful() ? Http::response($process->getOutput()) : Http::response('Not Found', 404);
        }

        if (preg_match('#^https://raw.githubusercontent.com/librenms/librenms/(refs/pull/\d+/head|[0-9a-f]{40})/(.+)$#', $url, $matches)) {
            $process = new Process(['git', 'show', "$matches[1]:" . rawurldecode($matches[2])], $this->upstream);
            $process->run();

            return $process->isSuccessful() ? Http::response($process->getOutput()) : Http::response('Not Found', 404);
        }

        return Http::response('Unexpected request', 500);
    });

    $this->manager = newManager();
});

afterEach(function () {
    File::deleteDirectory($this->tmp);

});

test('apply and remove keeps other changes', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c), 'new.txt' => "new file\n"]);

    // local changes made by the user
    write($this->local, 'file.txt', str_replace('nine', 'NINE', read('file.txt')));
    write($this->local, 'other.txt', "changed by user\n");

    $patch = $this->manager->fetch(1);
    expect($patch->files)->toBe([['status' => 'M', 'path' => 'file.txt'], ['status' => 'A', 'path' => 'new.txt']]);
    $this->manager->apply($patch);

    $this->assertStringContainsString('TWO', read('file.txt'));
    expect("$this->local/new.txt")->toBeFile();
    expect(newManager()->isApplied(newManager()->get(1)))->toBeTrue();

    newManager()->remove(newManager()->get(1));

    expect(read('file.txt'))->toBe("one\ntwo\nthree\nfour\nfive\nsix\nseven\neight\nNINE\nten\n");
    expect(read('other.txt'))->toBe("changed by user\n");
    $this->assertFileDoesNotExist("$this->local/new.txt");
    expect(newManager()->all())->toBeEmpty();
    $this->assertFileDoesNotExist("$this->tmp/storage/patches.json");
});

test('binary files', function () {
    write($this->upstream, 'old.png', "\x89PNG\0old");
    write($this->upstream, 'gone.png', "\x89PNG\0gone");
    commit($this->upstream, 'images');
    git($this->local, 'pull', '--quiet');

    createPullRequest(1, ['logo.png' => "\x89PNG\r\n\x1a\n\0\0binary\0data", 'old.png' => "\x89PNG\0new", 'gone.png' => null]);

    $patch = $this->manager->fetch(1);
    $this->assertStringNotContainsString('Binary files', $patch->diff);
    $this->manager->apply($patch);
    expect(read('logo.png'))->toBe("\x89PNG\r\n\x1a\n\0\0binary\0data");
    expect(read('old.png'))->toBe("\x89PNG\0new");
    $this->assertFileDoesNotExist("$this->local/gone.png");

    $this->manager->remove($this->manager->get(1));
    $this->assertFileDoesNotExist("$this->local/logo.png");
    expect(read('old.png'))->toBe("\x89PNG\0old");
    expect(read('gone.png'))->toBe("\x89PNG\0gone");
});

test('works without git repository', function () {
    // like the docker image: no .git, and it must not use a repository in a parent directory
    $parent = "$this->tmp/parent";
    mkdir($parent);
    git($parent, 'init', '--quiet');
    $this->local = "$parent/install";
    mkdir($this->local);
    git($this->upstream, 'archive', '--output', "$this->tmp/export.tar", 'master');
    new Process(['tar', '-xf', "$this->tmp/export.tar", '-C', $this->local])->mustRun();
    $manager = newManager();

    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c), 'logo.png' => "\x89PNG\0data"]);
    $manager->apply($manager->fetch(1));
    $this->assertStringContainsString('TWO', read('file.txt'));
    expect(read('logo.png'))->toBe("\x89PNG\0data");

    $manager->remove($manager->get(1));
    expect(read('file.txt'))->toBe("one\ntwo\nthree\nfour\nfive\nsix\nseven\neight\nnine\nten\n");
    $this->assertFileDoesNotExist("$this->local/logo.png");
    $this->assertFileDoesNotExist("$parent/file.txt");
});

test('applying again updates to new version', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    $this->manager->apply($this->manager->fetch(1));

    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO v2', $c)]);
    $this->manager->apply($this->manager->fetch(1));

    $this->assertStringContainsString("TWO v2\n", read('file.txt'));
    $this->assertStringNotContainsString("TWO\n", read('file.txt'));

    $this->manager->remove($this->manager->get(1));
    expect(read('file.txt'))->toBe("one\ntwo\nthree\nfour\nfive\nsix\nseven\neight\nnine\nten\n");
});

test('conflicting patch is not applied', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c), 'new.txt' => "new\n"]);
    write($this->local, 'file.txt', str_replace('two', 'user edit', read('file.txt')));

    try {
        $this->manager->apply($this->manager->fetch(1));
        $this->fail('Expected exception');
    } catch (PatchException) {
    }

    $this->assertFileDoesNotExist("$this->local/new.txt");
    // nothing partially applied
    expect($this->manager->all())->toBeEmpty();
});

test('remove unwinds overlapping patches', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    createPullRequest(2, ['other.txt' => "other changed\n"], rename: ['file.txt' => 'moved.txt']);

    $this->manager->apply($this->manager->fetch(1));
    $this->manager->apply($this->manager->fetch(2));
    $this->assertStringContainsString("TWO\n", read('moved.txt'));
    expect($this->manager->isApplied($this->manager->get(1)))->toBeTrue();

    // #2 moved the file changed by #1, so #1 can only be removed by unwinding #2
    expect($this->manager->remove($this->manager->get(1)))->toBe([]);

    expect(read('moved.txt'))->toBe("one\ntwo\nthree\nfour\nfive\nsix\nseven\neight\nnine\nten\n");
    $this->assertFileDoesNotExist("$this->local/file.txt");
    expect(array_keys($this->manager->all()))->toBe([2]);
    expect($this->manager->isApplied($this->manager->get(2)))->toBeTrue();
});

test('remove dependent patch', function () {
    createPullRequest(1, ['new.txt' => "new\n"]);
    createPullRequest(2, [], rename: ['other.txt' => 'new.txt'], from: 'pr-1');
    $this->apiResponses[2]['base']['ref'] = 'pr-1';

    $this->manager->apply($this->manager->fetch(1));
    $this->manager->apply($this->manager->fetch(2));

    $warnings = $this->manager->remove($this->manager->get(1));
    expect($warnings)->toHaveCount(1);
    expect($this->manager->get(2)->state)->toBe(Patch::SUSPENDED);
});

test('remove reverted patch', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    $this->manager->apply($this->manager->fetch(1));

    git($this->local, 'checkout', '--', 'file.txt');

    // changes discarded outside of lnms
    $this->manager->remove($this->manager->get(1));
    expect($this->manager->all())->toBeEmpty();
});

test('merged patch is never removed', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO v1', $c), 'v1.txt' => "v1 only\n"]);
    createPullRequest(2, ['other.txt' => "changed by 2\n"]);
    $this->manager->apply($this->manager->fetch(1));
    $this->manager->apply($this->manager->fetch(2));
    $patched = read('file.txt');

    mergeUpstream(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO final', $c)]);
    $target = fetchUpdate();

    // the merged patch blocks the update until the user removes it
    $manager = newManager();
    expect($manager->blockingPatches($target))->toBe([1 => ['file.txt']]);
    expect($manager->mergeCommit($manager->get(1), $target))->not->toBeNull();
    expect($manager->mergeCommit($manager->get(2), $target))->toBeNull();

    $pull = new Process(['git', 'pull', '--quiet'], $this->local);
    $pull->run();
    expect($pull->isSuccessful())->toBeFalse();
    expect(read('file.txt'))->toBe($patched);
    expect("$this->local/v1.txt")->toBeFile();
    expect(array_keys(newManager()->all()))->toBe([1, 2]);

    // after removing it, the update goes through and the other patch stays
    newManager()->remove(newManager()->get(1));
    expect(newManager()->blockingPatches($target))->toBe([]);
    git($this->local, 'pull', '--quiet');
    $this->assertStringContainsString('TWO final', read('file.txt'));
    $this->assertFileDoesNotExist("$this->local/v1.txt");
    expect(read('other.txt'))->toBe("changed by 2\n");
    expect(newManager()->isApplied(newManager()->get(2)))->toBeTrue();
});

test('pre update only reports blocking patches', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    createPullRequest(2, ['file.txt' => fn ($c) => str_replace('nine', 'NINE', $c)]);
    $this->manager->apply($this->manager->fetch(1));
    $this->manager->apply($this->manager->fetch(2));
    $patched = read('file.txt');

    mergeUpstream(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    $target = fetchUpdate();
    $this->app->instance(PatchManager::class, newManager());

    $this->artisan('patch:pre-update', ['target' => $target])
        ->expectsOutputToContain(trans('commands.patch:pre-update.merged', ['patch' => '#1 Test PR 1', 'pr' => 1]))
        ->expectsOutputToContain(trans('commands.patch:pre-update.conflict', ['patch' => '#2 Test PR 2', 'pr' => 2, 'files' => 'file.txt']))
        ->assertExitCode(1);

    expect(read('file.txt'))->toBe($patched);
    expect(array_keys(newManager()->all()))->toBe([1, 2]);
});

test('unmerged patch blocks update', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    $this->manager->apply($this->manager->fetch(1));
    $patched = read('file.txt');

    git($this->upstream, 'checkout', '--quiet', 'master');
    write($this->upstream, 'file.txt', str_replace('nine', 'upstream nine', read('file.txt', $this->upstream)));
    commit($this->upstream, 'Unrelated change to the same file');
    $target = fetchUpdate();

    expect(newManager()->blockingPatches($target))->toBe([1 => ['file.txt']]);
    expect(newManager()->mergeCommit(newManager()->get(1), $target))->toBeNull();

    // git keeps the patch and the code as they are
    $pull = new Process(['git', 'pull', '--quiet'], $this->local);
    $pull->run();
    expect($pull->isSuccessful())->toBeFalse();
    expect(read('file.txt'))->toBe($patched);
});

test('patch not blocking update stays applied', function () {
    createPullRequest(1, ['other.txt' => "changed by 1\n"]);
    $this->manager->apply($this->manager->fetch(1));

    git($this->upstream, 'checkout', '--quiet', 'master');
    write($this->upstream, 'file.txt', str_replace('nine', 'upstream nine', read('file.txt', $this->upstream)));
    commit($this->upstream, 'Unrelated change');
    $target = fetchUpdate();

    expect(newManager()->blockingPatches($target))->toBe([]);
    git($this->local, 'pull', '--quiet');
    $this->assertStringContainsString('upstream nine', read('file.txt'));
    expect(read('other.txt'))->toBe("changed by 1\n");
    expect(newManager()->isApplied(newManager()->get(1)))->toBeTrue();
});

test('reset preview matches what reset discards', function () {
    write($this->upstream, '.gitignore', "*.log\n");
    File::ensureDirectoryExists("$this->upstream/app");
    write($this->upstream, 'app/tracked.php', "tracked\n");
    commit($this->upstream, 'app');
    git($this->local, 'pull', '--quiet');

    createPullRequest(1, ['app/patched.php' => "patched\n"]);
    $this->manager->apply($this->manager->fetch(1));

    write($this->local, 'other.txt', "unstaged change\n");
    write($this->local, 'file.txt', "staged change\n");
    write($this->local, 'app/staged.php', "staged new\n");
    write($this->local, 'staged.txt', "staged new outside code dirs\n");
    git($this->local, 'add', 'file.txt', 'app/staged.php', 'staged.txt');
    write($this->local, 'app/untracked.php', "untracked\n");
    write($this->local, 'app/ignored.log', "ignored\n");
    write($this->local, 'notes.txt', "untracked outside code dirs\n");

    $preview = $this->manager->resetPreview();
    sort($preview['modified']);
    sort($preview['untracked']);
    expect($preview['modified'])->toBe(['file.txt', 'other.txt']);
    expect($preview['untracked'])->toBe(['app/patched.php', 'app/staged.php', 'app/untracked.php']);

    $this->manager->reset();

    expect(read('file.txt'))->toBe("one\ntwo\nthree\nfour\nfive\nsix\nseven\neight\nnine\nten\n");
    expect(read('other.txt'))->toBe("other\n");
    $this->assertFileDoesNotExist("$this->local/app/patched.php");
    $this->assertFileDoesNotExist("$this->local/app/staged.php");
    $this->assertFileDoesNotExist("$this->local/app/untracked.php");
    expect("$this->local/app/tracked.php")->toBeFile();

    // not in the preview, so not deleted
    expect("$this->local/app/ignored.log")->toBeFile();
    expect("$this->local/staged.txt")->toBeFile();
    expect("$this->local/notes.txt")->toBeFile();
    expect(newManager()->all())->toBeEmpty();
});

test('reset requires git repository', function () {
    $this->local = "$this->tmp/export";
    mkdir($this->local);
    write($this->local, 'file.txt', "keep me\n");
    $manager = newManager();

    foreach ([$manager->resetPreview(...), $manager->reset(...)] as $call) {
        try {
            $call();
            $this->fail('Expected exception');
        } catch (PatchException) {
        }
    }

    expect(read('file.txt'))->toBe("keep me\n");
});

test('remove all asks for confirmation', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    createPullRequest(2, ['other.txt' => "changed by 2\n"]);
    $this->manager->apply($this->manager->fetch(1));
    $this->manager->apply($this->manager->fetch(2));
    $this->app->instance(PatchManager::class, newManager());

    $this->artisan('patch:remove')
        ->expectsOutputToContain('#1 Test PR 1')
        ->expectsOutputToContain('#2 Test PR 2')
        ->expectsConfirmation(trans('commands.patch:remove.remove_all_prompt'), 'no')
        ->assertExitCode(1);
    expect(array_keys(newManager()->all()))->toBe([1, 2]);

    // naming the pull request does not ask
    $this->app->instance(PatchManager::class, newManager());
    $this->artisan('patch:remove', ['pull-request' => ['2']])->assertExitCode(0);
    expect(array_keys(newManager()->all()))->toBe([1]);

    $this->app->instance(PatchManager::class, newManager());
    $this->artisan('patch:remove')
        ->expectsConfirmation(trans('commands.patch:remove.remove_all_prompt'), 'yes')
        ->assertExitCode(0);
    expect(newManager()->all())->toBeEmpty();
    expect(read('other.txt'))->toBe("other\n");
});

test('reset lists files and can be declined', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    $this->manager->apply($this->manager->fetch(1));
    write($this->local, 'other.txt', "my own work\n");
    $this->app->instance(PatchManager::class, newManager());

    $this->artisan('patch:remove', ['--reset' => true])
        ->expectsOutputToContain(trans('commands.patch:remove.reset_warning'))
        ->expectsOutputToContain('other.txt')
        ->expectsConfirmation(trans('commands.patch:remove.reset_prompt'), 'no')
        ->assertExitCode(1);
    expect(read('other.txt'))->toBe("my own work\n");
    $this->assertStringContainsString('TWO', read('file.txt'));
    expect(array_keys(newManager()->all()))->toBe([1]);
});

test('composer files restored unless patch changes them', function () {
    write($this->upstream, 'composer.json', "{}\n");
    write($this->upstream, 'composer.lock', "{}\n");
    commit($this->upstream, 'composer');
    git($this->local, 'pull', '--quiet');

    // installing a plugin changes the composer files
    write($this->local, 'composer.json', "{\"plugin\": true}\n");
    expect($this->manager->restoreComposerFiles())->toBeTrue();
    expect(read('composer.json'))->toBe("{}\n");

    createPullRequest(1, ['composer.json' => "{\"patched\": true}\n"]);
    $this->manager->apply($this->manager->fetch(1));
    expect($this->manager->restoreComposerFiles())->toBeFalse();
    expect(read('composer.json'))->toBe("{\"patched\": true}\n");
});

test('parse number', function () {
    expect(Patch::parseNumber('123'))->toBe(123);
    expect(Patch::parseNumber('#123'))->toBe(123);
    expect(Patch::parseNumber('https://github.com/librenms/librenms/pull/123'))->toBe(123);
    expect(Patch::parseNumber('https://github.com/librenms/librenms/pull/123/files'))->toBe(123);
    expect(Patch::parseNumber('abc'))->toBeNull();
    expect(Patch::parseNumber('https://github.com/other/repo/pull/123'))->toBeNull();
});

test('release channel', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    git($this->local, 'checkout', '--quiet', '--detach');
    $this->manager->apply($this->manager->fetch(1));

    mergeUpstream(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    git($this->upstream, 'tag', '26.10.0');
    git($this->local, 'fetch', '--quiet', '--tags');
    $release = trim(git($this->local, 'rev-list', '--tags', '--max-count=1'));

    $manager = newManager();
    expect($manager->blockingPatches($release))->toBe([1 => ['file.txt']]);
    expect($manager->mergeCommit($manager->get(1), $release))->not->toBeNull();
});

test('merged detection without git hub api', function () {
    createPullRequest(1, ['file.txt' => fn ($c) => str_replace('two', 'TWO', $c)]);
    $patch = $this->manager->fetch(1);
    $this->apiResponses = [];
    // GitHub API not available
    expect($this->manager->mergeCommit($patch, 'HEAD'))->toBeNull();

    git($this->upstream, 'checkout', '--quiet', 'master');
    write($this->upstream, 'other.txt', "unrelated\n");
    commit($this->upstream, "Squashed change (#1)\n\n* fix typo (#12)");
    git($this->local, 'pull', '--quiet');

    expect($this->manager->mergeCommit($patch, 'HEAD'))->toBe(trim(git($this->local, 'rev-parse', 'HEAD')));
    expect($this->manager->mergeCommit(new Patch(12, '', '', []), 'HEAD'))->toBeNull();
});

test('fetch missing pull request', function () {
    $this->expectException(PatchException::class);
    $this->manager->fetch(99);
});

/**
 * Fetch upstream into the local repo like daily.sh and return the commit to update to
 */
function fetchUpdate(): string
{
    git($this->local, 'fetch', '--quiet');

    return trim(git($this->local, 'rev-parse', '@{upstream}'));
}

/**
 * Squash merge a version of the pull request into upstream master
 *
 * @param  array<string, string|callable>  $changes
 */
function mergeUpstream(int $pr, array $changes): void
{
    git($this->upstream, 'checkout', '--quiet', 'master');
    foreach ($changes as $file => $content) {
        File::ensureDirectoryExists(dirname("$this->upstream/$file"));
        write($this->upstream, $file, is_callable($content) ? $content(read($file, $this->upstream)) : $content);
    }
    $merge = commit($this->upstream, "Merged PR (#$pr)");
    $this->apiResponses[$pr]['merged'] = true;
    $this->apiResponses[$pr]['merge_commit_sha'] = $merge;
}

function newManager(): PatchManager
{
    return new PatchManager(new Binaries, $this->local, "$this->tmp/storage", 'http://github.test/');
}

/**
 * Create refs/pull/$pr/head in the upstream repo branched from master
 *
 * @param  array<string, string|callable|null>  $changes
 * @param  array<string, string>  $rename
 */
function createPullRequest(int $pr, array $changes, array $rename = [], string $from = 'master'): void
{
    git($this->upstream, 'checkout', '--quiet', '-B', "pr-$pr", $from);
    foreach ($changes as $file => $content) {
        if ($content === null) {
            git($this->upstream, 'rm', '--quiet', $file);
        } else {
            File::ensureDirectoryExists(dirname("$this->upstream/$file"));
            write($this->upstream, $file, is_callable($content) ? $content(read($file, $this->upstream)) : $content);
        }
    }
    foreach ($rename as $old => $new) {
        git($this->upstream, 'mv', '-f', $old, $new);
    }
    $sha = commit($this->upstream, "PR $pr");
    git($this->upstream, 'update-ref', "refs/pull/$pr/head", $sha);
    git($this->upstream, 'checkout', '--quiet', 'master');

    $this->apiResponses[$pr] = ['number' => $pr, 'title' => "Test PR $pr", 'user' => ['login' => 'tester'], 'merged' => false, 'merge_commit_sha' => null, 'head' => ['sha' => $sha]];
}

function commit(string $dir, string $message): string
{
    git($dir, 'add', '--all');
    git($dir, '-c', 'user.name=Test', '-c', 'user.email=test@example.com', 'commit', '--quiet', '-m', $message);

    return trim(git($dir, 'rev-parse', 'HEAD'));
}

function write(string $dir, string $file, string $content): void
{
    file_put_contents("$dir/$file", $content);
}

function read(string $file, ?string $dir = null): string
{
    return (string) file_get_contents(($dir ?? $this->local) . "/$file");
}

function git(string $dir, string ...$args): string
{
    // ignore the user's git config, such as commit signing
    $process = new Process(['git', ...$args], $dir, ['GIT_CONFIG_GLOBAL' => '/dev/null']);
    $process->mustRun();

    return $process->getOutput();
}