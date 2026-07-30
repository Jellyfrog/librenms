<?php

/*
 * ValidationFixTest.php
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
 * @copyright  2022 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use LibreNMS\Validations\Rrd\CheckRrdVersion;

uses(\LibreNMS\Tests\TestCase::class);

test('rrd version fix', function () {
    Storage::fake('base');
    Storage::disk('base')->put('config.php', <<<'EOF'
<?php
$config['test'] = 'rrdtool_version';
$config['rrdtool_version'] = '1.0';
$config["rrdtool_version"] = '1.1';
# comment

EOF
    );

    (new CheckRrdVersion())->fix();

    $actual = Storage::disk('base')->get('config.php');
    expect($actual)->toBe(<<<'EOF'
<?php
$config['test'] = 'rrdtool_version';
# comment

EOF);
    Storage::disk('base')->delete('config.php');
});
