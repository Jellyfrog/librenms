<?php

/**
 * MibTest.php
 *
 * Test Mib files for errors
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
use Illuminate\Support\Str;

uses(\LibreNMS\Tests\TestCase::class);

test('mib directory', function ($dir) {
    $output = shell_exec('snmptranslate -M +' . LibrenmsConfig::get('mib_dir') . ":$dir -m +ALL SNMPv2-MIB::system 2>&1");
    $errors = str_replace("SNMPv2-MIB::system\n", '', $output);

    expect($errors)->toBeEmpty("MIBs in $dir have errors!\n$errors");
})->with('mibDirs')->group('mibs');

test('duplicate mibs', function ($path, $file, $mib_name) {
    global $console_color;

    $file_path = "$path/$file";
    $highligted_mib = $console_color->convert("%r$mib_name%n");

    static $existing_mibs;
    if (is_null($existing_mibs)) {
        $existing_mibs = [];
    }

    if (isset($existing_mibs[$mib_name])) {
        $existing_mibs[$mib_name][] = $file_path;

        $this->fail("$highligted_mib has duplicates: " . implode(', ', $existing_mibs[$mib_name]));
    } else {
        $existing_mibs[$mib_name] = [$file_path];
    }
})->with('mibFiles')->group('mibs');

test('mib name matches', function ($path, $file, $mib_name) {
    global $console_color;

    $file_path = "$path/$file";
    $highlighted_file = $console_color->convert("%r$file_path%n");
    expect($file)->toEqual($mib_name, "$highlighted_file should be named $mib_name");
})->with('mibFiles')->group('mibs');

test('mib contents', function ($path, $file, $mib_name) {
    global $console_color;
    $file_path = "$path/$file";
    $highlighted_file = $console_color->convert("%r$file_path%n");

    $output = shell_exec('snmptranslate -M +' . LibrenmsConfig::get('mib_dir') . ":$path -m +$mib_name SNMPv2-MIB::system 2>&1");
    $errors = str_replace("SNMPv2-MIB::system\n", '', $output);

    expect($errors)->toBeEmpty("$highlighted_file has errors!\n$errors");
})->with('mibFiles')->group('mibs');

/**
 * Get a list of all mib files with the name of the mib.
 * Called for each test that uses it before class setup.
 *
 * @return array path, filename, mib_name
 */
dataset('mibFiles', function () {
    $mib_base = basePath('mibs');
    $install_dir = basePath();

    $file_list = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mib_base)) as $file) {
        /** @var SplFileInfo $file */
        if ($file->isDir()) {
            continue;
        }
        $mib_path = str_replace($mib_base . '/', '', $file->getPathname());
        $file_list[$mib_path] = [
            str_replace($install_dir, '.', $file->getPath()),
            $file->getFilename(),
            extractMibName($file->getPathname()),
        ];
    }

    return $file_list;
});

/**
 * Data provider: returns all MIB directories (main dir + subdirectories)
 *
 * @return array
 */
dataset('mibDirs', function () {
    $mib_base = basePath('mibs');

    $dirs = glob($mib_base . '/*', GLOB_ONLYDIR);
    array_unshift($dirs, $mib_base);

    $final_list = [];
    foreach ($dirs as $dir) {
        $relative_dir = ltrim(str_replace($mib_base, '', $dir), '/');
        $final_list[$relative_dir] = [$dir];
    }

    return $final_list;
});

/**
 * Extract the mib name from a file
 *
 * @throws Exception
 */
function extractMibName(string $file): string
{
    if ($handle = fopen($file, 'r')) {
        $header = '';
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);

            if (empty($trimmed) || Str::startsWith($trimmed, '--')) {
                continue;
            }

            $header .= " $trimmed";
            if (Str::contains($trimmed, 'DEFINITIONS')) {
                preg_match('/(\S+)\s+(?=DEFINITIONS)/', $header, $matches);
                fclose($handle);

                return $matches[1];
            }
        }
        fclose($handle);
    }

    throw new Exception("Could not extract mib name from file ($file)");
}

function basePath(string $subdir = ''): string
{
    $dir = rtrim(realpath(__DIR__ . '/..'), '/');

    return $subdir
        ? $dir . '/' . $subdir
        : $dir;
}
