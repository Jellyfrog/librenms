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
uses(LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use Illuminate\Support\Str;
use LibreNMS\Util\DataProviderCache;

test('mib directory', function ($dir): void {
    $output = shell_exec('snmptranslate -M +' . LibrenmsConfig::get('mib_dir') . ":$dir -m +ALL SNMPv2-MIB::system 2>&1");
    $errors = str_replace("SNMPv2-MIB::system\n", '', $output);

    expect($errors)->toBeEmpty("MIBs in $dir have errors!\n$errors");
})->with('mibDirs')->group('mibs');

test('duplicate mibs', function ($path, $file, $mib_name): void {
    $file_path = "$path/$file";

    static $existing_mibs;
    $existing_mibs ??= [];

    if (isset($existing_mibs[$mib_name])) {
        $existing_mibs[$mib_name][] = $file_path;

        $this->fail("$file_path has duplicates: " . implode(', ', $existing_mibs[$mib_name]));
    } else {
        $existing_mibs[$mib_name] = [$file_path];
    }
})->with('mibFiles')->group('mibs');

test('mib name matches', function ($path, $file, $mib_name): void {
    $file_path = "$path/$file";
    expect($file)->toEqual($mib_name, "$file_path should be named $mib_name");
})->with('mibFiles')->group('mibs');

test('mib contents', function ($path, $file, $mib_name): void {
    $output = shell_exec('snmptranslate -M +' . LibrenmsConfig::get('mib_dir') . ":$path -m +$mib_name SNMPv2-MIB::system 2>&1");
    $errors = str_replace("SNMPv2-MIB::system\n", '', $output);

    expect($errors)->toBeEmpty("$path/$file has errors!\n$errors");
})->with('mibFiles')->group('mibs');

/**
 * Get a list of all mib files with the name of the mib.
 * Called for each test that uses it before class setup.
 *
 * @return array path, filename, mib_name
 */
dataset('mibFiles', function () {
    $mib_base = mibTestBasePath('mibs');
    $install_dir = mibTestBasePath();

    return DataProviderCache::remember('mib_files', $mib_base, function () use ($mib_base, $install_dir) {
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
});

/**
 * Data provider: returns all MIB directories (main dir + subdirectories)
 *
 * @return array
 */
dataset('mibDirs', function () {
    $mib_base = mibTestBasePath('mibs');

    return DataProviderCache::remember('mib_dirs', $mib_base, function () use ($mib_base) {
        $dirs = glob($mib_base . '/*', GLOB_ONLYDIR);
        array_unshift($dirs, $mib_base);

        $final_list = [];
        foreach ($dirs as $dir) {
            $relative_dir = ltrim(str_replace($mib_base, '', $dir), '/');
            $final_list[$relative_dir] = [$dir];
        }

        return $final_list;
    });
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

/**
 * Repository root path, mirrors TestCase::basePath() which is not reachable from dataset closures
 */
function mibTestBasePath(string $subdir = ''): string
{
    $dir = dirname(__DIR__, 2);

    return $subdir
        ? $dir . '/' . $subdir
        : $dir;
}
