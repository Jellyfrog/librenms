<?php

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
uses(\LibreNMS\Tests\TestCase::class);

beforeEach(function () {
    $this->gitIgnoreFiles = [
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
});

test('git ignores exist', function () {
    foreach ($this->gitIgnoreFiles as $file) {
        expect($file)->toBeFile();
    }
});

test('git ignores mode', function () {
    foreach ($this->gitIgnoreFiles as $file) {
        expect(is_executable($file))->toBeFalse("$file should not be executable");
    }
});

test('git ignores not empty', function () {
    foreach ($this->gitIgnoreFiles as $file) {
        expect(filesize($file))->toBeGreaterThan(4, "$file is empty, it should not be");
    }
});
