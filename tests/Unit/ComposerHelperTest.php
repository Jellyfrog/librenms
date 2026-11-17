<?php

/*
 * ComposerHelperTest.php
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2026 Lee Clements
 * @author     Lee Clements <lee.clements@adaptivedatanetworks.com>
 */

use LibreNMS\ComposerHelper;

uses(LibreNMS\Tests\TestCase::class);

/*
 * getPlugins() is reached from Validations\Updates, which runs in the web UI
 * as well as from validate.php. Only validate.php pins the working directory
 * (validate.php: chdir(__DIR__)), so a working-directory-relative read
 * returns nothing under php-fpm -- and the modified-files exemption for
 * composer.json/composer.lock silently stops applying.
 */
test('get plugins is independent of the working directory', function (): void {
    $previous = getcwd();

    // Establish the expected answer from the install directory itself.
    chdir(base_path());
    $expected = ComposerHelper::getPlugins();

    $decoy = sys_get_temp_dir() . '/librenms-composer-plugins-' . getmypid();
    @mkdir($decoy, 0755, true);
    file_put_contents(
        $decoy . '/composer.plugins.json',
        '{"require":{"decoy/decoy":"^1.0"}}'
    );

    try {
        chdir($decoy);

        expect(ComposerHelper::getPlugins())->toBe($expected, 'getPlugins() must resolve composer.plugins.json against the install directory');
        $this->assertArrayNotHasKey(
            'decoy/decoy',
            ComposerHelper::getPlugins(),
            'getPlugins() must not read a composer.plugins.json from the working directory'
        );
    } finally {
        if ($previous !== false) {
            chdir($previous);
        }
        @unlink($decoy . '/composer.plugins.json');
        @rmdir($decoy);
    }
});

test('plugins file can be set in the environment', function (): void {
    $default = ComposerHelper::getPluginsFile();
    expect($default)->toBe(base_path('composer.plugins.json'));

    $file = sys_get_temp_dir() . '/librenms-composer-plugins-' . getmypid() . '.json';
    file_put_contents($file, '{"require":{"custom/package":"^1.0"}}');

    try {
        putenv("LIBRENMS_COMPOSER_PLUGINS=$file");

        expect(ComposerHelper::getPluginsFile())->toBe($file);
        expect(ComposerHelper::getPlugins())->toBe(['custom/package' => '^1.0']);
    } finally {
        putenv('LIBRENMS_COMPOSER_PLUGINS');
        @unlink($file);
    }

    expect(ComposerHelper::getPluginsFile())->toBe($default);
});
