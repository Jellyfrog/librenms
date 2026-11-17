<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use LibreNMS\Util\Binaries;

const BINARIES_TEST_BINARY = 'librenms-binaries-test';

beforeEach(function (): void {
    $this->path = getenv('PATH');
    $this->dir = sys_get_temp_dir() . '/librenms-binaries-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    putenv('PATH=' . $this->dir);
});

afterEach(function (): void {
    putenv($this->path === false ? 'PATH' : "PATH=$this->path");
    array_map(unlink(...), glob("$this->dir/*") ?: []);
    rmdir($this->dir);

});

test('finds binary in path', function (): void {
    $file = makeBinary(BINARIES_TEST_BINARY);

    expect((new Binaries)->find(BINARIES_TEST_BINARY))->toBe($file);
    expect((new Binaries)->path(BINARIES_TEST_BINARY))->toBe($file);
});

test('missing binary', function (): void {
    expect((new Binaries)->find(BINARIES_TEST_BINARY))->toBeNull();
    expect((new Binaries)->path(BINARIES_TEST_BINARY))->toBe(BINARIES_TEST_BINARY);
});

test('not executable', function (): void {
    $file = makeBinary(BINARIES_TEST_BINARY);
    chmod($file, 0644);

    expect((new Binaries)->find(BINARIES_TEST_BINARY))->toBeNull();
});

test('relative path entries are skipped', function (): void {
    makeBinary(BINARIES_TEST_BINARY);
    putenv('PATH=:.:relative:/opt/test/bin/');
    $cwd = getcwd();
    chdir($this->dir);

    try {
        expect((new Binaries)->find(BINARIES_TEST_BINARY))->toBeNull();
        expect((new Binaries)->searchDirs())->toBe(['/opt/test/bin/', '/usr/local/sbin', '/usr/local/bin', '/usr/sbin', '/usr/bin', '/sbin', '/bin']);
    } finally {
        chdir((string) $cwd);
    }
});

test('configured path is used', function (): void {
    makeBinary(BINARIES_TEST_BINARY);
    $configured = makeBinary('custom');
    config(['librenms.binaries.' . BINARIES_TEST_BINARY => $configured]);

    expect((new Binaries)->find(BINARIES_TEST_BINARY))->toBe($configured);
});

test('invalid configured path is not searched', function (): void {
    makeBinary(BINARIES_TEST_BINARY);
    makeBinary('custom');
    $cwd = getcwd();
    chdir($this->dir);

    try {
        foreach (['/nonexistent/' . BINARIES_TEST_BINARY, 'custom', $this->dir] as $configured) {
            config(['librenms.binaries.' . BINARIES_TEST_BINARY => $configured]);
            expect((new Binaries)->find(BINARIES_TEST_BINARY))->toBeNull($configured);
        }
    } finally {
        chdir((string) $cwd);
    }
});

test('legacy setting is used', function (): void {
    makeBinary('snmpwalk');
    $setting = makeBinary('snmpwalk-custom');
    LibrenmsConfig::set('snmpwalk', $setting);

    expect((new Binaries)->find('snmpwalk'))->toBe($setting);
    expect((new Binaries)->legacySetting('snmpwalk'))->toBe($setting);
});

test('configured path overrides legacy setting', function (): void {
    LibrenmsConfig::set('snmpwalk', makeBinary('snmpwalk-custom'));
    $configured = makeBinary('snmpwalk-env');
    config(['librenms.binaries.snmpwalk' => $configured]);

    expect((new Binaries)->find('snmpwalk'))->toBe($configured);
});

test('invalid legacy setting is skipped', function (): void {
    $file = makeBinary('snmpwalk');

    foreach (['/nonexistent/snmpwalk', 'snmpwalk', $this->dir] as $setting) {
        LibrenmsConfig::set('snmpwalk', $setting);
        expect((new Binaries)->legacySetting('snmpwalk'))->toBeNull($setting);
        expect((new Binaries)->find('snmpwalk'))->toBe($file, $setting);
    }
});

test('only binary settings are legacy', function (): void {
    $file = makeBinary(BINARIES_TEST_BINARY);
    LibrenmsConfig::set(BINARIES_TEST_BINARY, makeBinary('custom'));

    expect((new Binaries)->legacySetting(BINARIES_TEST_BINARY))->toBeNull();
    expect((new Binaries)->find(BINARIES_TEST_BINARY))->toBe($file);
});

test('found once per scope', function (): void {
    $file = makeBinary(BINARIES_TEST_BINARY);
    expect(resolve(Binaries::class)->find(BINARIES_TEST_BINARY))->toBe($file);

    unlink($file);
    expect(resolve(Binaries::class)->find(BINARIES_TEST_BINARY))->toBe($file);

    // a new job or request
    $this->app->forgetScopedInstances();
    expect(resolve(Binaries::class)->find(BINARIES_TEST_BINARY))->toBeNull();
});

function makeBinary(string $name): string
{
    $file = test()->dir . "/$name";
    file_put_contents($file, "#!/bin/sh\nexit 0\n");
    chmod($file, 0755);

    return $file;
}