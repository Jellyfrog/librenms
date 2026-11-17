<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use LibreNMS\Util\Binaries;


private const string BINARY = 'librenms-binaries-test';

beforeEach(function () {
    $this->path = getenv('PATH');
    $this->dir = sys_get_temp_dir() . '/librenms-binaries-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    putenv('PATH=' . $this->dir);
});

afterEach(function () {
    putenv($this->path === false ? 'PATH' : "PATH=$this->path");
    array_map(unlink(...), glob("$this->dir/*") ?: []);
    rmdir($this->dir);

});

test('finds binary in path', function () {
    $file = makeBinary(self::BINARY);

    expect((new Binaries)->find(self::BINARY))->toBe($file);
    expect((new Binaries)->path(self::BINARY))->toBe($file);
});

test('missing binary', function () {
    expect((new Binaries)->find(self::BINARY))->toBeNull();
    expect((new Binaries)->path(self::BINARY))->toBe(self::BINARY);
});

test('not executable', function () {
    $file = makeBinary(self::BINARY);
    chmod($file, 0644);

    expect((new Binaries)->find(self::BINARY))->toBeNull();
});

test('relative path entries are skipped', function () {
    makeBinary(self::BINARY);
    putenv('PATH=:.:relative:/opt/test/bin/');
    $cwd = getcwd();
    chdir($this->dir);

    try {
        expect((new Binaries)->find(self::BINARY))->toBeNull();
        expect((new Binaries)->searchDirs())->toBe(['/opt/test/bin/', '/usr/local/sbin', '/usr/local/bin', '/usr/sbin', '/usr/bin', '/sbin', '/bin']);
    } finally {
        chdir((string) $cwd);
    }
});

test('configured path is used', function () {
    makeBinary(self::BINARY);
    $configured = makeBinary('custom');
    config(['librenms.binaries.' . self::BINARY => $configured]);

    expect((new Binaries)->find(self::BINARY))->toBe($configured);
});

test('invalid configured path is not searched', function () {
    makeBinary(self::BINARY);
    makeBinary('custom');
    $cwd = getcwd();
    chdir($this->dir);

    try {
        foreach (['/nonexistent/' . self::BINARY, 'custom', $this->dir] as $configured) {
            config(['librenms.binaries.' . self::BINARY => $configured]);
            expect((new Binaries)->find(self::BINARY))->toBeNull($configured);
        }
    } finally {
        chdir((string) $cwd);
    }
});

test('legacy setting is used', function () {
    makeBinary('snmpwalk');
    $setting = makeBinary('snmpwalk-custom');
    LibrenmsConfig::set('snmpwalk', $setting);

    expect((new Binaries)->find('snmpwalk'))->toBe($setting);
    expect((new Binaries)->legacySetting('snmpwalk'))->toBe($setting);
});

test('configured path overrides legacy setting', function () {
    LibrenmsConfig::set('snmpwalk', makeBinary('snmpwalk-custom'));
    $configured = makeBinary('snmpwalk-env');
    config(['librenms.binaries.snmpwalk' => $configured]);

    expect((new Binaries)->find('snmpwalk'))->toBe($configured);
});

test('invalid legacy setting is skipped', function () {
    $file = makeBinary('snmpwalk');

    foreach (['/nonexistent/snmpwalk', 'snmpwalk', $this->dir] as $setting) {
        LibrenmsConfig::set('snmpwalk', $setting);
        expect((new Binaries)->legacySetting('snmpwalk'))->toBeNull($setting);
        expect((new Binaries)->find('snmpwalk'))->toBe($file, $setting);
    }
});

test('only binary settings are legacy', function () {
    $file = makeBinary(self::BINARY);
    LibrenmsConfig::set(self::BINARY, makeBinary('custom'));

    expect((new Binaries)->legacySetting(self::BINARY))->toBeNull();
    expect((new Binaries)->find(self::BINARY))->toBe($file);
});

test('found once per scope', function () {
    $file = makeBinary(self::BINARY);
    expect(resolve(Binaries::class)->find(self::BINARY))->toBe($file);

    unlink($file);
    expect(resolve(Binaries::class)->find(self::BINARY))->toBe($file);

    // a new job or request
    $this->app->forgetScopedInstances();
    expect(resolve(Binaries::class)->find(self::BINARY))->toBeNull();
});

function makeBinary(string $name): string
{
    $file = "$this->dir/$name";
    file_put_contents($file, "#!/bin/sh\nexit 0\n");
    chmod($file, 0755);

    return $file;
}