<?php

use App\Facades\LibrenmsConfig;
use LibreNMS\Util\Binaries;
use LibreNMS\Validations\Programs;
use LibreNMS\Validator;
use Mockery\MockInterface;

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);

beforeEach(function (): void {
    $this->path = getenv('PATH');
    $this->dir = sys_get_temp_dir() . '/librenms-legacy-binaries-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    putenv("PATH=$this->dir");
});

afterEach(function (): void {
    putenv($this->path === false ? 'PATH' : "PATH=$this->path");
    array_map(unlink(...), glob("$this->dir/*") ?: []);
    rmdir($this->dir);
});

test('fails when setting differs', function (): void {
    $setting = makeLegacyBinary('snmpwalk-custom');
    LibrenmsConfig::set('snmpwalk', $setting);
    $found = makeLegacyBinary('snmpwalk');

    $validator = legacyBinaryValidator('snmpwalk');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => $message === "snmpwalk is set to $setting in the LibreNMS config. Support for this setting will be removed, then $found will be used."
        && str_contains($fix, "add SNMPWALK_PATH=$setting to .env, then remove \$config['snmpwalk'] from config.php.")
        && str_contains($fix, "To use $found, remove"));

    (new Programs)->checkLegacySettings($validator);
});

test('fails when not found without setting', function (): void {
    if ((new Binaries)->search('nfdump') !== null) {
        $this->markTestSkipped('nfdump is installed in a standard bin directory');
    }

    LibrenmsConfig::set('nfdump', makeLegacyBinary('nfdump-custom'));

    $validator = legacyBinaryValidator('nfdump');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => str_ends_with($message, 'then nfdump will not be found.')
        && ! str_contains($fix, 'To use'));

    (new Programs)->checkLegacySettings($validator);
});

test('no override for fping6', function (): void {
    LibrenmsConfig::set('fping6', makeLegacyBinary('fping6-custom'));
    makeLegacyBinary('fping6');

    $validator = legacyBinaryValidator('fping6');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => ! str_contains($fix, '.env') && str_starts_with($fix, 'To use'));

    (new Programs)->checkLegacySettings($validator);
});

test('setting name differs from binary', function (): void {
    $setting = makeLegacyBinary('sendmail-custom');
    LibrenmsConfig::set('email_sendmail_path', $setting);
    makeLegacyBinary('sendmail');

    $validator = Mockery::mock(Validator::class);
    $validator->shouldReceive('fail')->withArgs(fn (string $message) => ! str_starts_with($message, 'email_sendmail_path is set to '))->zeroOrMoreTimes();
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => str_starts_with($message, "email_sendmail_path is set to $setting ")
        && str_contains($fix, "add SENDMAIL_PATH=$setting to .env, then remove \$config['email_sendmail_path'] from config.php."));

    (new Programs)->checkLegacySettings($validator);
});

test('remove stored setting', function (): void {
    $setting = makeLegacyBinary('snmpget-custom');
    LibrenmsConfig::persist('snmpget', $setting);

    $validator = legacyBinaryValidator('snmpget');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => str_contains($fix, 'then remove the setting with: lnms config:set snmpget.'));

    (new Programs)->checkLegacySettings($validator);
});

test('config file overrides stored setting', function (): void {
    LibrenmsConfig::persist('snmpget', makeLegacyBinary('snmpget-stored'));
    $setting = makeLegacyBinary('snmpget-file');
    LibrenmsConfig::set('snmpget', $setting);

    // as config.php would
    $validator = legacyBinaryValidator('snmpget');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => str_starts_with($message, "snmpget is set to $setting ")
        && str_contains($fix, "then remove \$config['snmpget'] from config.php.")
        && ! str_contains($fix, 'lnms config:set'));

    (new Programs)->checkLegacySettings($validator);
});

test('no failure when setting is found', function (): void {
    $file = makeLegacyBinary('rrdtool');
    symlink($file, "$this->dir/linked-rrdtool");
    LibrenmsConfig::set('rrdtool', "$this->dir/linked-rrdtool");

    $validator = legacyBinaryValidator('rrdtool');
    $validator->shouldNotReceive('fail');

    (new Programs)->checkLegacySettings($validator);
});

test('no failure when set in env', function (): void {
    LibrenmsConfig::set('virsh', makeLegacyBinary('virsh-custom'));
    config(['librenms.binaries.virsh' => makeLegacyBinary('virsh-env')]);

    $validator = legacyBinaryValidator('virsh');
    $validator->shouldNotReceive('fail');

    (new Programs)->checkLegacySettings($validator);
});

/**
 * Settings of other binaries on this system may be reported, ignore them
 */
function legacyBinaryValidator(string $binary): Validator&MockInterface
{
    $validator = Mockery::mock(Validator::class);
    $validator->shouldReceive('fail')->withArgs(fn (string $message) => ! str_starts_with($message, "$binary is set to "))->zeroOrMoreTimes();

    return $validator;
}

function makeLegacyBinary(string $name): string
{
    $file = test()->dir . "/$name";
    file_put_contents($file, "#!/bin/sh\nexit 0\n");
    chmod($file, 0755);

    return $file;
}