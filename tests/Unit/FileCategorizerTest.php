<?php

/**
 * FileCategorizerTest.php
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2020 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use Illuminate\Support\Arr;
use LibreNMS\Util\FileCategorizer;

uses(\LibreNMS\Tests\TestCase::class);

test('empty files', function () {
    $cat = new FileCategorizer();
    expect($cat->categorize())->toEqual(getCategorySkeleton());
});

test('ignored files', function () {
    assertCategorized([], [
        'docs/Nothing.md',
        'none',
        'includes/something.yaml',
        'html/test.css',
        'falsepythonpy',
        'falsephp',
        'falsebash',
        'resource',
        'vendor/misc/composer.lock',
        '/mibs/3com',
    ]);
});

test('php files', function () {
    assertCategorized([
        'php' => [
            'includes/polling/sensors.inc.php',
            'misc/test.php',
            'app/Http/Kernel.php',
            'LibreNMS/Modules/Mpls.php',
        ],
    ]);
});

test('docs files', function () {
    assertCategorized([
        'docs' => [
            'doc/CNAME',
            'doc/Developing/Creating-Release.md',
            'mkdocs.yml',
        ],
    ]);
});

test('python', function () {
    assertCategorized([
        'python' => [
            'python.py',
            'LibreNMS/__init__.py',
        ],
    ]);
});

test('bash', function () {
    assertCategorized([
        'bash' => [
            'daily.sh',
            'scripts/deploy-docs.sh',
        ],
    ]);
});

test('svg', function () {
    assertCategorized([
        'svg' => [
            'html/images/os/zte.svg',
            'html/images/logos/zyxel.svg',
            'html/svg/403.svg',
        ],
    ]);
});

test('resources', function () {
    assertCategorized([
        'resources' => [
            'resources/js/app.js',
            'resources/js/components/LibrenmsSetting.vue',
            'resources/views/layouts/librenmsv1.blade.php',
        ],
        'php' => [
            'resources/views/layouts/librenmsv1.blade.php',
        ],
    ]);
});

test('os files', function () {
    assertCategorized([
        'os' => ['ftd', '3com', 'adva_fsp150', 'saf-integra-b'],
        'os-files' => [
            'tests/data/ftd.json',
            'tests/data/3com_4200.json',
            'tests/data/adva_fsp150_ge114pro.json',
            'tests/data/saf-integra-b.json',
        ],
    ]);

    assertCategorized([
        'os' => ['ciscowap', 'xos', 'ciscosb', 'linux'],
        'os-files' => [
            'tests/snmpsim/ciscowap.snmprec',
            'tests/snmpsim/xos_x480.snmprec',
            'tests/snmpsim/ciscosb_esw540_8p.snmprec',
            'tests/snmpsim/linux_fbsd-nfs-client-v1.snmprec',
        ],
    ]);

    assertCategorized([
        'os' => ['arris-c4', 'ios'],
        'os-files' => [
            'includes/discovery/sensors/temperature/arris-c4.inc.php',
            'includes/polling/entity-physical/ios.inc.php',
        ],
        'php' => [
            'includes/discovery/sensors/temperature/arris-c4.inc.php',
            'includes/polling/entity-physical/ios.inc.php',
        ],
    ]);

    assertCategorized([
        'os' => ['3com', 'arris-dsr4410md', 'adva_fsp3kr7', 'xirrus_aos'],
        'os-files' => [
            'LibreNMS/OS/ThreeCom.php',
            'LibreNMS/OS/ArrisDsr4410md.php',
            'LibreNMS/OS/AdvaFsp3kr7.php',
            'LibreNMS/OS/XirrusAos.php',
        ],
        'php' => [
            'LibreNMS/OS/ThreeCom.php',
            'LibreNMS/OS/ArrisDsr4410md.php',
            'LibreNMS/OS/AdvaFsp3kr7.php',
            'LibreNMS/OS/XirrusAos.php',
        ],
    ]);

    assertCategorized([
        'os' => ['dlink', 'eltex-olt'],
        'os-files' => [
            'resources/definitions/os_detection/dlink.yaml',
            'resources/definitions/os_discovery/eltex-olt.yaml',
        ],
    ]);
});

test('full checks', function () {
    assertCategorized(['full-checks' => ['composer.lock']]);
    assertCategorized(['full-checks' => ['.github/workflows/test.yml']], ['other', '.github/workflows/test.yml']);

    assertCategorized([
        'os' => ['3com', 'calix', 'ptp650', 'dd-wrt', 'arista_eos'],
        'os-files' => [
            'tests/data/3com.json',
            'tests/snmpsim/calix.snmprec',
            'LibreNMS/OS/Ptp650.php',
            'resources/definitions/os_detection/dd-wrt.yaml',
            'resources/definitions/os_discovery/arista_eos.yaml',
        ],
        'php' => [
            'LibreNMS/OS/Ptp650.php',
        ],
        'full-checks' => [true],
    ], [
        'tests/data/3com.json',
        'tests/snmpsim/calix.snmprec',
        'LibreNMS/OS/Ptp650.php',
        'resources/definitions/os_detection/dd-wrt.yaml',
        'resources/definitions/os_discovery/arista_eos.yaml',
    ]);
});

function assertCategorized($expected, $input = null, $message = '')
{
    $files = $input ?? array_unique(Arr::flatten(Arr::except($expected, ['os'])));
    // os is a virtual category
    $expected = array_merge(getCategorySkeleton(), $expected);

    expect((new FileCategorizer($files))->categorize())->toEqual($expected, $message);
}

function getCategorySkeleton()
{
    return [
        'php' => [],
        'docs' => [],
        'python' => [],
        'bash' => [],
        'svg' => [],
        'resources' => [],
        'full-checks' => [],
        'os-files' => [],
        'os' => [],
    ];
}
