<?php

/**
 * CommonFunctionsTest.php
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
 * @copyright  2016 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Facades\LibrenmsConfig;
use Illuminate\Support\Str;
use LibreNMS\Enum\PortAssociationMode;
use LibreNMS\Util\Clean;
use LibreNMS\Util\StringHelpers;
use LibreNMS\Util\Validate;

uses(\LibreNMS\Tests\TestCase::class);

test('str contains', function () {
    $data = 'This is a test. Just Testing.';

    expect(Str::contains($data, 'Just'))->toBeTrue();
    expect(Str::contains($data, 'just'))->toBeFalse();

    expect(Str::contains($data, 'juSt', ignoreCase: true))->toBeTrue();
    expect(Str::contains($data, 'nope', ignoreCase: true))->toBeFalse();

    expect(Str::contains($data, ['not', 'this', 'This']))->toBeTrue();
    expect(Str::contains($data, ['not', 'this']))->toBeFalse();

    expect(Str::contains($data, ['not', 'thIs'], ignoreCase: true))->toBeTrue();
    expect(Str::contains($data, ['not', 'anything'], ignoreCase: true))->toBeFalse();
});

test('starts with', function () {
    $data = 'This is a test. Just Testing that.';

    expect(Str::startsWith($data, 'This'))->toBeTrue();
    expect(Str::startsWith($data, 'this'))->toBeFalse();

    expect(Str::startsWith($data, ['this', 'Test', 'This']))->toBeTrue();
    expect(Str::startsWith($data, ['this', 'Test']))->toBeFalse();
});

test('ends with', function () {
    $data = 'This is a test. Just Testing';

    expect(Str::endsWith($data, 'Testing'))->toBeTrue();
    expect(Str::endsWith($data, 'testing'))->toBeFalse();

    expect(Str::endsWith($data, ['this', 'Testing', 'This']))->toBeTrue();
    expect(Str::endsWith($data, ['this', 'Test']))->toBeFalse();
});

test('rrd descriptions', function () {
    $data = 'Toner, S/N:CR_UM-16021314488.';
    expect(\LibreNMS\Data\Store\Rrd::safeDescr($data))->toEqual('Toner, S/N CR_UM-16021314488.');
});

test('set null', function () {
    expect(set_null('BAD-DATA'))->toBeNull();
    expect(set_null(0))->toEqual(0);
    expect(set_null(25))->toEqual(25);
    expect(set_null(-25))->toEqual(-25);
    expect(set_null(' ', 99))->toEqual(99);
    expect(set_null(-25, null, 0))->toBeNull();
    expect(set_null(2, 0, 2))->toEqual(2);
});

test('display', function () {
    expect(Clean::html('<html>string</html>', []))->toEqual('&lt;html&gt;string&lt;/html&gt;');
    expect(Clean::html('<script>alert("test")</script>', []))->toEqual('&lt;script&gt;alert("test")&lt;/script&gt;');
    expect(Clean::html("Is your name O\'reilly?", []))->toEqual("Is your name O'reilly?");
    expect(Clean::html("Is your name O\'reilly?"))->toEqual("Is your name O'reilly?");
    expect(Clean::html(''))->toEqual('');
    expect(Clean::html(null))->toEqual('');

    $tmp_config = [
        'HTML.Allowed' => 'b,iframe,i,ul,li,h1,h2,h3,h4,br,p',
        'HTML.Trusted' => true,
        'HTML.SafeIframe' => true,
    ];

    expect(Clean::html('<b>Bold</b>', $tmp_config))->toEqual('<b>Bold</b>');
    expect(Clean::html('<script>alert("test")</script>', $tmp_config))->toEqual('');
});

test('string to class', function () {
    expect(StringHelpers::toClass('OS', 'LibreNMS\\OS\\'))->toBe('LibreNMS\OS\Os');
    expect(StringHelpers::toClass('spaces name', null))->toBe('SpacesName');
    expect(StringHelpers::toClass('dash-name', null))->toBe('DashName');
    expect(StringHelpers::toClass('underscore_name', null))->toBe('UnderscoreName');
    expect(StringHelpers::toClass('all OF-thEm_NaMe', 'LibreNMS\\'))->toBe('LibreNMS\\AllOfThemName');
});

test('is valid hostname', function () {
    expect(Validate::hostname('a'))->toBeTrue('a');
    expect(Validate::hostname('a.'))->toBeTrue('a.');
    expect(Validate::hostname('0'))->toBeTrue('0');
    expect(Validate::hostname('a.b'))->toBeTrue('a.b');
    expect(Validate::hostname('localhost'))->toBeTrue('localhost');
    expect(Validate::hostname('google.com'))->toBeTrue('google.com');
    expect(Validate::hostname('news.google.co.uk'))->toBeTrue('news.google.co.uk');
    expect(Validate::hostname('xn--fsqu00a.xn--0zwm56d'))->toBeTrue('xn--fsqu00a.xn--0zwm56d');
    expect(Validate::hostname('www.averylargedomainthatdoesnotreallyexist.com'))->toBeTrue('www.averylargedomainthatdoesnotreallyexist.com');
    expect(Validate::hostname('cont-ains.h-yph-en-s.com'))->toBeTrue('cont-ains.h-yph-en-s.com');
    expect(Validate::hostname('cisco-3750x'))->toBeTrue('cisco-3750x');
    expect(Validate::hostname('cisco_3750x'))->toBeTrue('cisco_3750x');
    expect(Validate::hostname('goo gle.com'))->toBeFalse('goo gle.com');
    expect(Validate::hostname('google..com'))->toBeFalse('google..com');
    expect(Validate::hostname('google.com '))->toBeFalse('google.com ');
    expect(Validate::hostname('google-.com'))->toBeFalse('google-.com');
    expect(Validate::hostname('.google.com'))->toBeFalse('.google.com');
    expect(Validate::hostname('..google.com'))->toBeFalse('..google.com');
    expect(Validate::hostname('<script'))->toBeFalse('<script');
    expect(Validate::hostname('alert('))->toBeFalse('alert(');
    expect(Validate::hostname('.'))->toBeFalse('.');
    expect(Validate::hostname('..'))->toBeFalse('..');
    expect(Validate::hostname(' '))->toBeFalse('Just a space');
    expect(Validate::hostname('-'))->toBeFalse('-');
    expect(Validate::hostname(''))->toBeFalse('Empty string');
});

test('resolve glues', function () {
    $this->dbSetUp();

    expect(ResolveGlues(['dbSchema'], 'device_id'))->toBeFalse();

    expect(ResolveGlues(['devices'], 'device_id'))->toBe(['devices.device_id']);
    expect(ResolveGlues(['sensors'], 'device_id'))->toBe(['sensors.device_id']);

    // does not work right with current code
    //        $expected = array('bill_data.bill_id', 'bill_ports.port_id', 'ports.device_id');
    //        $this->assertSame($expected, ResolveGlues(array('bill_data'), 'device_id'));
    $expected = ['application_metrics.app_id', 'applications.device_id'];
    expect(ResolveGlues(['application_metrics'], 'device_id'))->toBe($expected);

    $expected = ['state_translations.state_index_id', 'sensors_to_state_indexes.sensor_id', 'sensors.device_id'];
    expect(ResolveGlues(['state_translations'], 'device_id'))->toBe($expected);

    $expected = ['ipv4_addresses.port_id', 'ports.device_id'];
    expect(ResolveGlues(['ipv4_addresses'], 'device_id'))->toBe($expected);

    $this->dbTearDown();
});

test('format hostname', function () {
    $device_dns = [
        'hostname' => 'test.librenms.org',
        'sysName' => 'Testing DNS',
    ];
    $invalid_dns = [
        'hostname' => 'Not DNS',
        'sysName' => 'Testing Invalid DNS',
    ];
    $device_ip = [
        'hostname' => '192.168.1.2',
        'sysName' => 'Testing IP',
    ];
    $invalid_ip = [
        'hostname' => '256.168.1.2',
        'sysName' => 'Testing Invalid IP',
    ];
    $custom_display = [
        'hostname' => 'test.librenms.org',
        'sysName' => 'sysName',
        'display' => 'Custom Display ({{ $hostname }} {{ $sysName }})',
    ];

    // default {{ $hostname }}
    LibrenmsConfig::set('device_display_default', null);
    expect(format_hostname($device_dns))->toEqual('test.librenms.org');
    expect(format_hostname($invalid_dns))->toEqual('Not DNS');
    expect(format_hostname($device_ip))->toEqual('192.168.1.2');
    expect(format_hostname($invalid_ip))->toEqual('256.168.1.2');
    expect(format_hostname($custom_display))->toEqual('Custom Display (test.librenms.org sysName)');

    // ip to sysname
    LibrenmsConfig::set('device_display_default', '{{ $sysName_fallback }}');
    expect(format_hostname($device_dns))->toEqual('test.librenms.org');
    expect(format_hostname($invalid_dns))->toEqual('Not DNS');
    expect(format_hostname($device_ip))->toEqual('Testing IP');
    expect(format_hostname($invalid_ip))->toEqual('256.168.1.2');
    expect(format_hostname($custom_display))->toEqual('Custom Display (test.librenms.org sysName)');

    // sysname
    LibrenmsConfig::set('device_display_default', '{{ $sysName }}');
    expect(format_hostname($device_dns))->toEqual('Testing DNS');
    expect(format_hostname($invalid_dns))->toEqual('Testing Invalid DNS');
    expect(format_hostname($device_ip))->toEqual('Testing IP');
    expect(format_hostname($invalid_ip))->toEqual('Testing Invalid IP');
    expect(format_hostname($custom_display))->toEqual('Custom Display (test.librenms.org sysName)');

    // custom
    $custom_ip = ['display' => 'IP: {{ $ip }}', 'hostname' => '1.1.1.1', 'ip' => '2.2.2.2'];
    expect(format_hostname($custom_ip))->toEqual('IP: 1.1.1.1');
    $custom_ip['hostname'] = 'not_ip';
    expect(format_hostname($custom_ip))->toEqual('IP: 2.2.2.2');
    $custom_ip['overwrite_ip'] = '3.3.3.3';
    expect(format_hostname($custom_ip))->toEqual('IP: 3.3.3.3');
});

test('port association', function () {
    $modes = [
        1 => 'ifIndex',
        2 => 'ifName',
        3 => 'ifDescr',
        4 => 'ifAlias',
    ];

    expect(PortAssociationMode::getModes())->toEqual($modes);
    expect(PortAssociationMode::getName(1))->toEqual('ifIndex');
    expect(PortAssociationMode::getId('ifIndex'))->toEqual(1);
    expect(PortAssociationMode::getName(666))->toBeNull();
    expect(PortAssociationMode::getId('lucifer'))->toBeNull();
});
