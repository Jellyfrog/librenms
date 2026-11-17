<?php

/**
 * OSDiscoveryTest.php
 *
 * Test all discovery for all OS
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
 *
 * @copyright  2016 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Models\Device;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LibreNMS\Data\Source\Snmp\SnmpBackendInterface;
use LibreNMS\Modules\Core;
use LibreNMS\Tests\Mocks\SnmprecSnmpBackend;
use LibreNMS\Util\Debug;

uses(LibreNMS\Tests\TestCase::class)->group('os');

/** @var array<string, int> $unchecked_files */
$unchecked_files = [];

beforeAll(function () use (&$unchecked_files): void {
    $glob = dirname(__DIR__, 2) . '/tests/snmpsim/*.snmprec';

    $unchecked_files = array_flip(array_filter(
        array_map(fn ($file) => basename($file, '.snmprec'), glob($glob)),
        fn ($file) => ! Str::contains($file, '@') && ! in_array($file, ['snmpquerymock_regression', 'snmprec_regression'], true)
    ));
});

beforeEach(function (): void {
    // read the snmprec files directly, detection does not need a real snmp agent
    // and starting a net-snmp process for every query makes this test several times slower
    $this->app->bind(SnmpBackendInterface::class, SnmprecSnmpBackend::class);
});

/**
 * Provides a list of OS to generate tests.
 */
function osProvider(): array
{
    $definitionsPath = dirname(__DIR__, 2) . '/resources/definitions/os_detection';
    $yamlFiles = glob($definitionsPath . '/*.yaml');

    $config_os = [];
    foreach ($yamlFiles as $file) {
        $os = basename($file, '.yaml');
        $config_os[] = $os;
    }

    $excluded_os = [
        'default',
        'generic',
        'ping',
    ];
    $filtered_os = array_diff($config_os, $excluded_os);

    $snmprecFiles = array_map(fn ($f) => basename($f, '.snmprec'), glob(dirname(__DIR__, 2) . '/tests/snmpsim/*.snmprec'));
    $snmprecFiles = array_filter($snmprecFiles, fn ($f) => ! str_contains($f, '@'));

    $all_os = [];
    foreach ($filtered_os as $os) {
        $prefix = $os . '_';
        $matchedFiles = [];
        foreach ($snmprecFiles as $file) {
            if ($file === $os || str_starts_with($file, $prefix)) {
                $matchedFiles[] = $file;
            }
        }
        $all_os[$os] = [$os, $matchedFiles];
    }

    return $all_os;
}

dataset('os_detection', fn () => osProvider());

/**
 * Set up and test an os
 * If $filename is not set, it will use the snmprec file matching $expected_os
 *
 * @param  string  $expected_os  The os we should get back from getHostOS()
 * @param  string  $filename  the name of the snmprec file to use
 */
function checkOS($expected_os, $filename = null)
{
    $start = microtime(true);

    $community = $filename ?: $expected_os;
    $os = Core::detectOS(genDevice($community));
    if ($os !== $expected_os) {
        // Re-run with full debug output only on mismatch to capture diagnostics
        $log_driver = Log::getDefaultDriver();
        Debug::set();
        Debug::setVerbose();
        Debug::enableCliDebugOutput();
        ob_start();
        Log::setDefaultDriver('stdout');
        $os = Core::detectOS(genDevice($community));
        $output = ob_get_contents();
        Log::setDefaultDriver($log_driver);
        ob_end_clean();
        Debug::set(false);
        Debug::setVerbose(false);
        Debug::disableCliDebugOutput();

        test()->assertEquals($expected_os, $os, "Test file: $community.snmprec\n$output");
    }

    test()->assertLessThan(60, microtime(true) - $start, "OS $expected_os took longer than 60s to detect");
    test()->assertEquals($expected_os, $os);
}

/**
 * Generate a fake $device array
 *
 * @param  string  $community  The snmp community to set
 * @return Device resulting device array
 */
function genDevice($community): Device
{
    $device = new Device([
        'hostname' => test()->getSnmpsimIp(),
        'os' => 'generic',
    ]);

    $device->setRelation('pollingMethods', collect([
        new \App\Models\DevicePollingMethod([
            'method_type' => \LibreNMS\Enum\PollingMethodType::Snmp,
            'enabled' => true,
            'affects_availability' => true,
        ]),
    ]));

    $secret = new \App\Models\Secret([
        'secret_type' => \LibreNMS\Enum\SecretType::SnmpCommunity,
        'data' => [
            'community' => $community,
        ],
    ]);
    $snmpMethod = $device->pollingMethods->first();
    if ($snmpMethod) {
        $snmpMethod->setRelation('secret', $secret);
        $snmpMethod->settings = [
            'port' => test()->getSnmpsimPort(),
            'timeout' => 3,
            'retries' => 0,
            'max_repeaters' => 25,
        ];
    }

    return $device;
}

test('valid OS names', function (): void {
    $os = array_keys(osProvider());

    $invalid_os_name = array_filter($os, fn ($os_name) => preg_match('/[^a-z0-9\-]/', (string) $os_name));

    // DO NOT ADD ANY OS HERE!
    $exceptions = [
        'adva_fsp150',
        'adva_fsp3kr7',
        'adva_xg300',
        'allworx_voip',
        'arista_eos',
        'xirrus_aos',
        'ies52xxM',
        'polycomLens',
    ];
    $invalid_os_name = array_diff($invalid_os_name, $exceptions);

    $this->assertEmpty($invalid_os_name, 'Invalid OS name found: ' . implode(', ', $invalid_os_name));
});

/**
 * Populate a list of files to check and make sure it isn't empty
 */
test('have files to test', function () use (&$unchecked_files): void {
    $this->assertNotEmpty($unchecked_files);
});

test('have variants lowercase', function () use (&$unchecked_files): void {
    $this->assertNotEmpty($unchecked_files);

    foreach ($unchecked_files as $file => $count) {
        $underscore_pos = strpos($file, '_');
        if ($underscore_pos !== false) {
            $variant = substr($file, $underscore_pos + 1);
            $this->assertSame(strtolower($variant), $variant, 'Test file variant not lowercase');
        }
    }
});

/**
 * Test each OS provided by the os_detection dataset
 */
test('OS detection', function ($os_name, array $files = []) use (&$unchecked_files): void {
    if (empty($files)) {
        $this->fail("No snmprec files found for $os_name!");
    }

    foreach ($files as $file) {
        checkOS($os_name, $file);
        unset($unchecked_files[$file]);  // This file has been tested
    }
})->with('os_detection');

/**
 * Test that all files have been tested (removed from $unchecked_files)
 */
test('all files tested', function () use (&$unchecked_files): void {
    $this->assertEmpty(
        $unchecked_files,
        'Not all snmprec files were checked: ' . print_r(array_keys($unchecked_files), true)
    );
})->depends('OS detection');
