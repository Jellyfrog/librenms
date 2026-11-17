<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Facades\LibrenmsConfig;
use LibreNMS\Util\Binaries;
use LibreNMS\Validations\Programs;
use LibreNMS\Validator;
use Mockery\MockInterface;


beforeEach(function () {
    $this->path = getenv('PATH');
    $this->dir = sys_get_temp_dir() . '/librenms-legacy-binaries-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
    putenv("PATH=$this->dir");
});

afterEach(function () {
    putenv($this->path === false ? 'PATH' : "PATH=$this->path");
    array_map(unlink(...), glob("$this->dir/*") ?: []);
    rmdir($this->dir);

});

test('fails when setting differs', function () {
    $setting = makeBinary('snmpwalk-custom');
    LibrenmsConfig::set('snmpwalk', $setting);
    $found = makeBinary('snmpwalk');

    $validator = validator('snmpwalk');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => $message === "snmpwalk is set to $setting in the LibreNMS config. Support for this setting will be removed, then $found will be used."
        && str_contains($fix, "add SNMPWALK_PATH=$setting to .env, then remove \$config['snmpwalk'] from config.php.")
        && str_contains($fix, "To use $found, remove"));

    (new Programs)->checkLegacySettings($validator);
});

test('fails when not found without setting', function () {
    if ((new Binaries)->search('nfdump') !== null) {
        $this->markTestSkipped('nfdump is installed in a standard bin directory');
    }

    LibrenmsConfig::set('nfdump', makeBinary('nfdump-custom'));

    $validator = validator('nfdump');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => str_ends_with($message, 'then nfdump will not be found.')
        && ! str_contains($fix, 'To use'));

    (new Programs)->checkLegacySettings($validator);
});

test('no override for fping6', function () {
    LibrenmsConfig::set('fping6', makeBinary('fping6-custom'));
    makeBinary('fping6');

    $validator = validator('fping6');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => ! str_contains($fix, '.env') && str_starts_with($fix, 'To use'));

    (new Programs)->checkLegacySettings($validator);
});

test('setting name differs from binary', function () {
    $setting = makeBinary('sendmail-custom');
    LibrenmsConfig::set('email_sendmail_path', $setting);
    makeBinary('sendmail');

    $validator = Mockery::mock(Validator::class);
    $validator->shouldReceive('fail')->withArgs(fn (string $message) => ! str_starts_with($message, 'email_sendmail_path is set to '))->zeroOrMoreTimes();
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => str_starts_with($message, "email_sendmail_path is set to $setting ")
        && str_contains($fix, "add SENDMAIL_PATH=$setting to .env, then remove \$config['email_sendmail_path'] from config.php."));

    (new Programs)->checkLegacySettings($validator);
});

test('remove stored setting', function () {
    $setting = makeBinary('snmpget-custom');
    LibrenmsConfig::persist('snmpget', $setting);

    $validator = validator('snmpget');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => str_contains($fix, 'then remove the setting with: lnms config:set snmpget.'));

    (new Programs)->checkLegacySettings($validator);
});

test('config file overrides stored setting', function () {
    LibrenmsConfig::persist('snmpget', makeBinary('snmpget-stored'));
    $setting = makeBinary('snmpget-file');
    LibrenmsConfig::set('snmpget', $setting);

    // as config.php would
    $validator = validator('snmpget');
    $validator->shouldReceive('fail')->once()->withArgs(fn (string $message, string $fix) => str_starts_with($message, "snmpget is set to $setting ")
        && str_contains($fix, "then remove \$config['snmpget'] from config.php.")
        && ! str_contains($fix, 'lnms config:set'));

    (new Programs)->checkLegacySettings($validator);
});

test('no failure when setting is found', function () {
    $file = makeBinary('rrdtool');
    symlink($file, "$this->dir/linked-rrdtool");
    LibrenmsConfig::set('rrdtool', "$this->dir/linked-rrdtool");

    $validator = validator('rrdtool');
    $validator->shouldNotReceive('fail');

    (new Programs)->checkLegacySettings($validator);
});

test('no failure when set in env', function () {
    LibrenmsConfig::set('virsh', makeBinary('virsh-custom'));
    config(['librenms.binaries.virsh' => makeBinary('virsh-env')]);

    $validator = validator('virsh');
    $validator->shouldNotReceive('fail');

    (new Programs)->checkLegacySettings($validator);
});

/**
 * Settings of other binaries on this system may be reported, ignore them
 */
function validator(string $binary): Validator&MockInterface
{
    $validator = Mockery::mock(Validator::class);
    $validator->shouldReceive('fail')->withArgs(fn (string $message) => ! str_starts_with($message, "$binary is set to "))->zeroOrMoreTimes();

    return $validator;
}

function makeBinary(string $name): string
{
    $file = "$this->dir/$name";
    file_put_contents($file, "#!/bin/sh\nexit 0\n");
    chmod($file, 0755);

    return $file;
}