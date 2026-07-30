<?php

/**
 * OSModulesTest.php
 *
 * Test discovery and poller modules
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
 * @copyright  2017 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Facades\LibrenmsConfig;
use Illuminate\Support\Arr;
use LibreNMS\Data\Source\Fping;
use LibreNMS\Data\Source\FpingResponse;
use LibreNMS\Exceptions\FileNotFoundException;
use LibreNMS\Exceptions\InvalidModuleException;
use LibreNMS\Util\ModuleList;
use LibreNMS\Util\ModuleTestHelper;
use LibreNMS\Util\Number;
use PHPUnit\Util\Color;

uses(\LibreNMS\Tests\DBTestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    // backup modules
    $this->discoveryModules = LibrenmsConfig::get('discovery_modules');
    $this->pollerModules = LibrenmsConfig::get('poller_modules');
});

afterEach(function () {
    // restore modules
    LibrenmsConfig::set('discovery_modules', $this->discoveryModules);
    LibrenmsConfig::set('poller_modules', $this->pollerModules);

});

test('OS data is valid', function ($os, $variant, $modules) {
    // special case if dataset throws exception
    if ($os === false) {
        $this->fail($modules);
    }

    expect($modules)->not->toBeEmpty("No modules to test for $os $variant");
})->with('dumped_data')->group('os');

test('OS', function ($os, $variant, array $modules) {
    // Lock testing time
    $this->travelTo(new \DateTime('2022-01-01 00:00:00'));
    $this->requireSnmpsim();  // require snmpsim for tests
    // stub out Eventlog::log and Fping->ping, we don't need to store them for these tests
    stubClasses();

    $results = null;

    try {
        $helper = new ModuleTestHelper(new ModuleList($modules), $os, $variant);
        $helper->setQuiet();

        $filename = $helper->getJsonFilepath(true);
        $expected_data = $helper->getTestData();
        $results = $helper->generateTestData($this->getSnmpsimIp(), $this->getSnmpsimPort(), true);
    } catch (FileNotFoundException|InvalidModuleException $e) {
        $this->fail($e->getMessage());
    }

    if (is_null($results)) {
        $this->fail("$os: Failed to collect data.");
    }

    // output all discovery and poller output if debug mode is enabled for pest
    $debug_output = in_array('--debug', $_SERVER['argv'], true);

    foreach ($modules as $module => $module_status) {
        $expected = $expected_data[$module]['discovery'] ?? null;
        $actual = $results[$module]['discovery'] ?? null;
        checkTestData($expected, $actual, 'Discovered', $os, $module, $filename, $helper, $debug_output);

        // modules without polling
        if (in_array($module, ['route', 'vlans'])) {
            continue;
        }

        if (isset($expected_data[$module]['poller'])) {
            if ($expected_data[$module]['poller'] !== 'matches discovery') {
                $expected = $expected_data[$module]['poller']; // we have specific poller data, update expected
            }
            // pass through discovery expected data
        } else {
            $expected = null; // no poller data, clear discovery's expected
        }

        $actual = $results[$module]['poller'] ?? null;
        checkTestData($expected, $actual, 'Polled', $os, $module, $filename, $helper, $debug_output);
    }

    expect(true)->toBeTrue("Tested $os successfully"); // avoid no asserts error

    DeviceCache::flush(); // clear cached devices
    $this->travelBack();
})->with('dumped_data')->group('os', 'os-modules');

dataset('dumped_data', function () {
    $modules = [];
    $baseDir = realpath(__DIR__ . '/..');

    if (getenv('TEST_MODULES')) {
        $modules = explode(',', getenv('TEST_MODULES'));
    }

    try {
        return ModuleTestHelper::findOsWithData($modules, base_path: $baseDir);
    } catch (InvalidModuleException $e) {
        // special case for exception
        return [[false, false, $e->getMessage()]];
    }
});

function stubClasses(): void
{
    app()->bind(\App\Models\Eventlog::class, function ($app) {
        $mock = \Mockery::mock(\App\Models\Eventlog::class);
        $mock->shouldReceive('_log');

        return $mock;
    });

    app()->bind(Fping::class, function ($app) {
        $mock = \Mockery::mock(Fping::class);
        $mock->shouldReceive('ping')->andReturn(FpingResponse::artificialUp());

        return $mock;
    });
}

function checkTestData(?array $expected, ?array $actual, string $type, string $os, mixed $module, string $filename, ModuleTestHelper $helper, bool $debug_output): void
{
    // try simple and fast comparison first, if that fails, do a costly/well formatted comparison
    if ($expected != $actual) {
        expect($actual)->not->toBeNull("OS $os: $type $module no data generated when it is expected");
        expect($expected)->not->toBeNull("OS $os: $type $module generates data when none is expected");

        $message = Color::colorize('bg-red', "OS $os: $type $module data does not match that found in $filename");
        $message .= PHP_EOL;
        $message .= ($type == 'Discovered'
            ? $helper->getDiscoveryOutput($debug_output ? null : $module)
            : $helper->getPollerOutput($debug_output ? null : $module));

        // convert to dot notation so the array is flat and easier to compare visually
        $expected = Arr::dot($expected);
        $actual = Arr::dot($actual);

        // json will store 43.0 as 43, Number::cast will change those to integers too
        foreach ($actual as $index => $value) {
            if (is_float($value)) {
                $actual[$index] = Number::cast($value);
            }
        }

        expect($actual)->toBe($expected, $message);
    }
}