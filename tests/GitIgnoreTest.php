<?php

use LibreNMS\Tests\TestCase;

uses(TestCase::class);

/**
 * GitIgnoreTest.php
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
 * @copyright  2019 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

$gitIgnoreFiles = [
    '.gitignore',
    'bootstrap/cache/.gitignore',
    'cache/.gitignore',
    'logs/.gitignore',
    'resources/views/alerts/templates/.gitignore',
    'rrd/.gitignore',
    'storage/app/.gitignore',
    'storage/app/public/.gitignore',
    'storage/debugbar/.gitignore',
    'storage/framework/cache/.gitignore',
    'storage/framework/sessions/.gitignore',
    'storage/framework/testing/.gitignore',
    'storage/framework/views/.gitignore',
    'storage/logs/.gitignore',
];

test('gitignores exist', function () use ($gitIgnoreFiles) {
    foreach ($gitIgnoreFiles as $file) {
        $this->assertFileExists($file);
    }
});

test('gitignores mode', function () use ($gitIgnoreFiles) {
    foreach ($gitIgnoreFiles as $file) {
        $this->assertFalse(is_executable($file), "$file should not be executable");
    }
});

test('gitignores not empty', function () use ($gitIgnoreFiles) {
    foreach ($gitIgnoreFiles as $file) {
        $this->assertGreaterThan(4, filesize($file), "$file is empty, it should not be");
    }
});
