<?php

/**
 * CiHelperTest.php
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

use LibreNMS\Util\CiHelper;

uses(LibreNMS\Tests\TestCase::class);

test('set flags', function (): void {
    $helper = new CiHelper();
    $allFalse = array_map(fn ($flag) => false, getDefaultFlags());
    $allTrue = array_map(fn ($flag) => true, getDefaultFlags());

    $helper->setFlags($allFalse);
    expect($helper->getFlags())->toEqual($allFalse);

    $helper->setFlags($allTrue);
    expect($helper->getFlags())->toEqual($allTrue);

    $helper->setFlags(['undefined_flag' => false]);
    expect($helper->getFlags())->toEqual($allTrue);

    $helper->setFlags(['full' => false]);
    $testOne = $allTrue;
    $testOne['full'] = false;
    expect($helper->getFlags())->toEqual($testOne);
});

test('defaults', function (): void {
    $helper = new CiHelper();
    expect($helper->getFlags())->toEqual(getDefaultFlags());
});

test('no files', function (): void {
    putenv('FILES=none');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip' => true,
        'style_skip' => true,
        'unit_skip' => true,
        'web_skip' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
    ]);
});

test('set os', function (): void {
    $helper = new CiHelper();
    $helper->setOS(['netonix', 'e3meter']);
    assertFlagsSet($helper, [
        'unit_os' => true,
    ]);

    putenv('FILES=none');
    $helper = new CiHelper();
    $helper->setOS(['netonix']);
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip' => true,
        'style_skip' => true,
        'web_skip' => true,
        'unit_os' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
    ]);

    putenv('FILES=resources/definitions/os_detection/ios.yaml tests/data/fxos.json');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip' => true,
        'style_skip' => true,
        'web_skip' => true,
        'unit_os' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
    ]);
});

test('set modules', function (): void {
    $helper = new CiHelper();
    $helper->setModules(['sensors', 'processors']);
    assertFlagsSet($helper, [
        'unit_modules' => true,
    ]);

    putenv('FILES=none');
    $helper = new CiHelper();
    $helper->setModules(['os']);
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip' => true,
        'style_skip' => true,
        'web_skip' => true,
        'unit_modules' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
    ]);

    putenv('FILES=none');
    $helper = new CiHelper();
    $helper->setOS(['linux']);
    $helper->setModules(['os']);
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip' => true,
        'style_skip' => true,
        'web_skip' => true,
        'unit_os' => true,
        'unit_modules' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
    ]);

    putenv('FILES=resources/definitions/os_detection/ios.yaml tests/data/fxos.json');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip' => true,
        'style_skip' => true,
        'web_skip' => true,
        'unit_os' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
    ]);

    putenv('FILES=resources/definitions/os_detection/ios.yaml tests/data/fxos.json');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip' => true,
        'style_skip' => true,
        'web_skip' => true,
        'unit_os' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
    ]);
});

test('file categorization', function (): void {
    putenv('FILES=LibreNMS/Alert/Transport/Sensu.php includes/services.inc.php');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
    ]);

    putenv('FILES=/daily.sh includes/services.inc.php');
    $helper = new CiHelper();
    $helper->detectChangedFiles();

    assertFlagsSet($helper, [
        'lint_skip_python' => true,
    ]);

    putenv('FILES=daily.sh LibreNMS/__init__.py');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'style_skip' => true,
        'unit_skip' => true,
        'web_skip' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
    ]);

    putenv('FILES=includes/polling/sensors/ios.inc.php');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
        'unit_os' => true,
    ]);

    putenv('FILES=html/images/os/ios.svg');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip' => true,
        'style_skip' => true,
        'web_skip' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
        'unit_svg' => true,
    ]);

    putenv('FILES=html/images/os/ios.svg');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'lint_skip' => true,
        'style_skip' => true,
        'web_skip' => true,
        'lint_skip_php' => true,
        'lint_skip_phpstan' => true,
        'lint_skip_python' => true,
        'lint_skip_bash' => true,
        'unit_svg' => true,
    ]);

    putenv('FILES=.github/workflows/test.yml');
    $helper = new CiHelper();
    $helper->detectChangedFiles();
    assertFlagsSet($helper, [
        'full' => true,
    ]);
});

function assertFlagsSet(CiHelper $helper, $flags = [])
{
    $full = getDefaultFlags();
    foreach ($flags as $name => $value) {
        $full[$name] = $value;
        expect($helper->getFlag($name))->toEqual($value, "Flag $name incorrect.");
    }

    expect($helper->getFlags())->toEqual($full);
}

function getDefaultFlags()
{
    return [
        'lint_enable' => true,
        'style_enable' => true,
        'unit_enable' => true,
        'web_enable' => false,
        'lint_skip' => false,
        'style_skip' => false,
        'unit_skip' => false,
        'web_skip' => false,
        'lint_skip_php' => false,
        'lint_skip_phpstan' => false,
        'lint_skip_python' => false,
        'lint_skip_bash' => false,
        'unit_os' => false,
        'unit_docs' => false,
        'unit_svg' => false,
        'unit_modules' => false,
        'docs_changed' => false,
        'ci' => false,
        'commands' => false,
        'fail-fast' => false,
        'full' => false,
        'quiet' => false,
        'os-modules-only' => false,
    ];
}
