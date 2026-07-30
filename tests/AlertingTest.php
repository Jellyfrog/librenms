<?php

/**
 * AlertingTest.php
 *
 * Tests for alerting functionality.
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
 * @copyright  2016 Neil Lathwood
 * @author     Neil Lathwood <neil@lathwood.co.uk>
 */
uses(LibreNMS\Tests\TestCase::class);
test('json alert collection', function (): void {
    $rules = get_rules_from_json();
    expect($rules)->toBeArray();
    foreach ($rules as $rule) {
        expect($rule)->toBeArray();
    }
});

test('transports', function (): void {
    foreach (getTransportFiles() as $file => $_unused) {
        $parts = explode('/', (string) $file);
        $transport = ucfirst(str_replace('.php', '', array_pop($parts)));
        $class = 'LibreNMS\\Alert\\Transport\\' . $transport;
        expect(class_exists($class))->toBeTrue("The transport $transport does not exist");
        expect(new $class)->toBeInstanceOf(LibreNMS\Interfaces\Alert\Transport::class);
    }
});

function getTransportFiles(): RegexIterator
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('LibreNMS/Alert/Transport'));

    return new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);
}
