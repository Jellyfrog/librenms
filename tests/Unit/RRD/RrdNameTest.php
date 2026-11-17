<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Facades\Rrd;
use App\Models\Device;
use App\Models\Eventlog;
use Illuminate\Support\Facades\File;
use LibreNMS\Data\Store\Rrd as RrdStore;
use LibreNMS\Exceptions\RrdNotFoundException;
use LibreNMS\RRD\RrdPath;
use LibreNMS\RRD\RrdProcess;

test('path', function () {
    $testpath = Rrd::name('pswcs07.wcs.network', ['storage', 'hrstorage', 'C:\ Label: Serial Number 7e9e7a18']);
    expect((string) $testpath)->toBe('pswcs07.wcs.network/storage-hrstorage-C___Label__Serial_Number_7e9e7a18.rrd');
    expect($testpath->relativePath())->toBe((string) $testpath);
    expect($testpath->fullPath())->toBe(rtrim(LibrenmsConfig::get('rrd_dir'), '/') . '/pswcs07.wcs.network/storage-hrstorage-C___Label__Serial_Number_7e9e7a18.rrd');
});

test('rename file', function () {
    $rrd_dir = sys_get_temp_dir() . '/librenms-rrd-test-' . uniqid();
    LibrenmsConfig::set('rrd_dir', $rrd_dir);
    $eventlog = Mockery::mock(Eventlog::class);
    $eventlog->shouldReceive('_log');
    $this->app->instance(Eventlog::class, $eventlog);
    $device = new Device(['hostname' => 'renamehost']);

    try {
        mkdir("$rrd_dir/renamehost", 0777, true);
        touch("$rrd_dir/renamehost/bgp-2001_0db8_0000_0000_0000_0000_0000_0001.rrd");

        expect(Rrd::renameFile($device, 'bgp-2001_0db8_0000_0000_0000_0000_0000_0001', ['bgp', '2001:db8::1']))->toBeTrue();
        $this->assertFileDoesNotExist("$rrd_dir/renamehost/bgp-2001_0db8_0000_0000_0000_0000_0000_0001.rrd");
        expect("$rrd_dir/renamehost/bgp-2001_db8__1.rrd")->toBeFile();
    } finally {
        File::deleteDirectory($rrd_dir);
    }
});

test('rename file remote rrdcached notifies once', function () {
    $remote_files = ['remotehost/toner-Black.rrd'];
    $events = fakeRemoteRrdcached($remote_files);
    $device = new Device(['hostname' => 'remotehost']);
    $rrd = new RrdStore();

    expect($rrd->renameFile($device, ['toner', 'Black'], ['toner', 'black', '1']))->toBeTrue();
    $remote_files[] = 'remotehost/toner-black-1.rrd';
    // created by the first write
    expect($rrd->renameFile($device, ['toner', 'Black'], ['toner', 'black', '1']))->toBeTrue();

    expect($events->getArrayCopy())->toBe(['Unable to rename remotehost/toner-Black.rrd to remotehost/toner-black-1.rrd on remote rrdcached server, rename manually to keep history']);
});

test('rename file remote rrdcached no old file', function () {
    $remote_files = [];
    $events = fakeRemoteRrdcached($remote_files);

    expect(new RrdStore()->renameFile(new Device(['hostname' => 'remotehost']), ['toner', 'Black'], ['toner', 'black', '1']))->toBeTrue();

    expect($events)->toHaveCount(0);
});

test('rename file no rrdcached does not query rrdtool', function () {
    $remote_files = ['localhost/toner-Black.rrd'];
    $events = fakeRemoteRrdcached($remote_files);
    LibrenmsConfig::set('rrdcached', '');

    expect(new RrdStore()->renameFile(new Device(['hostname' => 'localhost']), ['toner', 'Black'], ['toner', 'black', '1']))->toBeTrue();

    expect($events)->toHaveCount(0);
});

/**
 * Fake a remote rrdcached server holding $remote_files (relative paths) and collect eventlog messages
 *
 * @param  string[]  $remote_files
 * @return \ArrayObject<int, string>
 */
function fakeRemoteRrdcached(array &$remote_files): \ArrayObject
{
    LibrenmsConfig::set('rrd_dir', sys_get_temp_dir() . '/librenms-rrd-test-' . uniqid());
    LibrenmsConfig::set('rrdcached', 'rrdcached.example.com:42217');
    LibrenmsConfig::set('rrdtool_version', '1.7');

    $process = Mockery::mock(RrdProcess::class);
    $process->shouldReceive('run')->andReturnUsing(function (string $command) use (&$remote_files): string {
        $this->assertNotSame('', LibrenmsConfig::get('rrdcached'), 'rrdtool should not be queried without rrdcached');
        $file = explode(' ', $command)[1];
        if (! in_array($file, $remote_files)) {
            throw new RrdNotFoundException("opening '$file': No such file or directory");
        }

        return '1798761600';
    });
    $process->shouldReceive('stop');
    $this->app->bind(RrdProcess::class, fn () => $process);

    /** @var \ArrayObject<int, string> $events */
    $events = new \ArrayObject();
    $eventlog = Mockery::mock(Eventlog::class);
    $eventlog->shouldReceive('_log')->andReturnUsing(function (string $text) use ($events): void {
        $events[] = $text;
    });
    $this->app->instance(Eventlog::class, $eventlog);

    return $events;
}

test('check dir exists creates directory', function () {
    checkDirExistsCreates('');
});

test('check dir exists creates directory with rrdcached', function () {
    checkDirExistsCreates('unix:/var/run/rrdcached.sock');
});

test('check dir exists failure', function () {
    $rrd_dir = sys_get_temp_dir() . '/librenms-rrd-test-' . uniqid();
    touch($rrd_dir);
    // a file where the directory should be prevents creation
    LibrenmsConfig::set('rrd_dir', $rrd_dir);

    try {
        LibrenmsConfig::set('rrdcached', '');
        expect(RrdStore::checkDirExists(RrdPath::make('newhost')))->toBeFalse();

        // failure is expected with a remote rrdcached
        LibrenmsConfig::set('rrdcached', 'rrdcached.example.com:42217');
        expect(RrdStore::checkDirExists(RrdPath::make('newhost')))->toBeTrue();
    } finally {
        unlink($rrd_dir);
    }
});

function checkDirExistsCreates(string $rrdcached): void
{
    $rrd_dir = sys_get_temp_dir() . '/librenms-rrd-test-' . uniqid();
    LibrenmsConfig::set('rrd_dir', $rrd_dir);
    LibrenmsConfig::set('rrdcached', $rrdcached);

    try {
        expect(RrdStore::checkDirExists(RrdPath::make('newhost')))->toBeTrue();
        expect("$rrd_dir/newhost")->toBeDirectory();
    } finally {
        File::deleteDirectory($rrd_dir);
    }
}
