<?php

/**
 * SVGTest.php
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
 * @copyright  2017 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

uses(\LibreNMS\Tests\TestCase::class)->group('svg');
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

test('svgcontains png', function () {
    foreach (getSvgFiles() as $file => $_unused) {
        $svg = file_get_contents($file);

        expect(Str::contains($svg, 'data:image/'))->toBeFalse("$file contains a bitmap image, please use a regular png or valid svg");
    }
});

test('svghas length width', function () {
    foreach (getSvgFiles() as $file => $_unused) {
        if ($file == 'html/images/safari-pinned-tab.svg') {
            continue;
        }

        if (str_starts_with((string) $file, 'html/images/custommap/background/')) {
            continue;
        }

        $svg = file_get_contents($file);

        expect(preg_match('/<svg[^>]*(length|width)=/', $svg, $matches))->toEqual(0, "$file: SVG files must not contain length or width attributes ");
    }
});

test('svghas view box', function () {
    foreach (getSvgFiles() as $file => $_unused) {
        $svg = file_get_contents($file);

        expect(Str::contains($svg, 'viewBox'))->toBeTrue("$file: SVG files must have the viewBox attribute set");
    }
});

function getSvgFiles(): RegexIterator
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('html/images'));

    return new RegexIterator($iterator, '/^.+\.svg$/i', RecursiveRegexIterator::GET_MATCH);
}
