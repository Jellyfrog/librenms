<?php

/*
 * LocationTest.php
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
 * @copyright  2021 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Location;
use LibreNMS\Interfaces\Geocoder;
use LibreNMS\Util\Dns;
use Mockery\MockInterface;

uses(LibreNMS\Tests\TestCase::class);

test('can set location', function (): void {
    $device = Device::factory()->make();
    /** @var Device $device */
    $device->setLocation('Where');

    expect('Where')->toEqual($device->location->location);
    expect($device->location->lat)->toBeNull();
    expect($device->location->lng)->toBeNull();

    $device->setLocation(null);
    expect($device->location)->toBeNull();
});

test('can not set location', function (): void {
    $device = Device::factory()->make();
    /** @var Device $device */
    $location = Location::factory()->make();
    /** @var Location $location */
    $device->override_sysLocation = true;
    $device->setLocation($location->location);
    expect($device->location)->toBeNull();
});

test('can set encoded location', function (): void {
    LibrenmsConfig::set('geoloc.dns', false);
    $device = Device::factory()->make();

    /** @var Device $device */
    // valid coords
    $location = Location::factory()->withCoordinates()->make();
    /** @var Location $location */
    $device->setLocation("$location->location [$location->lat,$location->lng]", true);
    expect($device->location->location)->toEqual("$location->location [$location->lat,$location->lng]");
    expect($device->location->display())->toEqual($location->location);
    expect($device->location->lat)->toEqual($location->lat);
    expect($device->location->lng)->toEqual($location->lng);

    // with space
    $location = Location::factory()->withCoordinates()->make();
    /** @var Location $location */
    $device->setLocation("$location->location [$location->lat, $location->lng]", true);
    expect($device->location->location)->toEqual("$location->location [$location->lat, $location->lng]");
    expect($device->location->display())->toEqual($location->location);
    expect($device->location->display(true))->toEqual("$location->location [$location->lat,$location->lng]");
    expect($device->location->lat)->toEqual($location->lat);
    expect($device->location->lng)->toEqual($location->lng);

    // invalid coords
    $location = Location::factory()->withCoordinates()->make(['lat' => 251.5007138]);
    /** @var Location $location */
    $name = "$location->location [$location->lat,$location->lng]";
    $device->setLocation($name, true);
    expect($device->location->location)->toEqual($name);
    expect($device->location->display())->toEqual($name);
    expect($device->location->display(true))->toEqual($name);
    expect($device->location->lat)->toBeNull();
    expect($device->location->lng)->toBeNull();
});

test('can handle given coordinates', function (): void {
    LibrenmsConfig::set('geoloc.dns', false);
    $device = Device::factory()->make();
    /** @var Device $device */
    $location = Location::factory()->withCoordinates()->make();
    /** @var Location $location */
    $device->setLocation($location);
    expect($device->location->location)->toEqual($location->location);
    expect($device->location->display())->toEqual($location->location);
    expect($device->location->display(true))->toEqual("$location->location [$location->lat,$location->lng]");
    expect($device->location->lat)->toEqual($location->lat);
    expect($device->location->lng)->toEqual($location->lng);
});

test('can not set fixed coordinates', function (): void {
    $device = Device::factory()->make();
    /** @var Device $device */
    $locationOne = Location::factory()->withCoordinates()->make();
    /** @var Location $locationOne */
    $locationTwo = Location::factory(['location' => $locationOne->location])->withCoordinates()->make();
    /** @var Location $locationTwo */
    $device->setLocation($locationOne);
    expect($device->location->lat)->toEqual($locationOne->lat);
    expect($device->location->lng)->toEqual($locationOne->lng);

    $device->location->fixed_coordinates = true;
    $device->setLocation($locationTwo);
    expect($device->location->lat)->toEqual($locationOne->lat);
    expect($device->location->lng)->toEqual($locationOne->lng);

    $device->location->fixed_coordinates = false;
    $device->setLocation($locationTwo);
    expect($device->location->lat)->toEqual($locationTwo->lat);
    expect($device->location->lng)->toEqual($locationTwo->lng);
});

test('dns lookup', function (): void {
    $example = 'SW1A2AA.find.me.uk';
    $expected = ['lat' => 51.50354111111111, 'lng' => -0.12766972222222223];

    $this->mock(Net_DNS2_Resolver::class, function (MockInterface $mock) use ($example, $expected): void {
        $loc = new Net_DNS2_RR_LOC();
        $loc->name = $example;
        $loc->latitude = $expected['lat'];
        $loc->longitude = $expected['lng'];
        $answer = (object) ['answer' => [$loc]];
        $mock->shouldReceive('query')->with($example, 'LOC')->once()->andReturn($answer);
    });

    $result = $this->app->make(Dns::class)->getCoordinates($example);

    expect($result)->toEqual($expected);
});

test('can set dns coordinate', function (): void {
    LibrenmsConfig::set('geoloc.dns', true);
    $device = Device::factory()->make();
    /** @var Device $device */
    $location = Location::factory()->withCoordinates()->make();
    /** @var Location $location */
    $this->mock(Dns::class, function (MockInterface $mock) use ($location): void {
        $mock->shouldReceive('getCoordinates')->once()->andReturn($location->only(['lat', 'lng']));
    });

    $device->setLocation($location->location, true);
    expect($device->location->location)->toEqual($location->location);
    expect($device->location->lat)->toEqual($location->lat);
    expect($device->location->lng)->toEqual($location->lng);

    LibrenmsConfig::set('geoloc.dns', false);
    $device->setLocation('No DNS', true);
    expect($device->location->location)->toEqual('No DNS');
    expect($device->location->lat)->toBeNull();
    expect($device->location->lng)->toBeNull();
});

test('can set by api', function (): void {
    $device = Device::factory()->make();
    /** @var Device $device */
    $location = Location::factory()->withCoordinates()->make();
    /** @var Location $location */
    $this->mock(Geocoder::class, function (MockInterface $mock) use ($location): void {
        $mock->shouldReceive('getCoordinates')->once()->andReturn($location->only(['lat', 'lng']));
    });

    // Disable DNS lookup since we're not testing that.
    LibrenmsConfig::set('geoloc.dns', false);

    LibrenmsConfig::set('geoloc.latlng', false);
    $device->setLocation('No API', true);
    expect($device->location->location)->toEqual('No API');
    expect($device->location->lat)->toBeNull();
    expect($device->location->lng)->toBeNull();

    LibrenmsConfig::set('geoloc.latlng', true);
    $device->setLocation('API', true);
    expect($device->location->location)->toEqual('API');
    expect($device->location->lat)->toEqual($location->lat);
    expect($device->location->lng)->toEqual($location->lng);

    // preset coord = skip api
    $device->setLocation('API', true);
    expect($device->location->lat)->toEqual($location->lat);
    expect($device->location->lng)->toEqual($location->lng);
});

test('correct precedence', function (): void {
    $device = Device::factory()->make();
    /** @var Device $device */
    $location_encoded = Location::factory()->withCoordinates()->make();
    /** @var Location $location_encoded */
    $location_fixed = Location::factory()->withCoordinates()->make();
    /** @var Location $location_fixed */
    $location_api = Location::factory()->withCoordinates()->make();
    /** @var Location $location_api */
    $location_dns = Location::factory()->withCoordinates()->make();
    /** @var Location $location_dns */
    LibrenmsConfig::set('geoloc.dns', true);
    $this->mock(Dns::class, function (MockInterface $mock) use ($location_dns): void {
        $mock->shouldReceive('getCoordinates')->times(3)->andReturn(
            $location_dns->only(['lat', 'lng']),
            [],
            []
        );
    });

    LibrenmsConfig::set('geoloc.latlng', true);
    $this->mock(Geocoder::class, function (MockInterface $mock) use ($location_api): void {
        $mock->shouldReceive('getCoordinates')->once()->andReturn($location_api->only(['lat', 'lng']));
    });

    // fixed first
    $location_fixed->location = "$location_fixed [-42, 42]";
    // encoded should not be used
    $device->setLocation($location_fixed, true);
    expect($device->location->lat)->toEqual($location_fixed->lat);
    expect($device->location->lng)->toEqual($location_fixed->lng);

    // then encoded
    $device->setLocation($location_encoded->display(true), true);
    expect($device->location->lat)->toEqual($location_encoded->lat);
    expect($device->location->lng)->toEqual($location_encoded->lng);

    // then dns
    $device->setLocation($location_encoded->location, true);
    expect($device->location->lat)->toEqual($location_dns->lat);
    expect($device->location->lng)->toEqual($location_dns->lng);

    // then api
    $device->setLocation($location_encoded->location, true);
    expect($device->location->lat)->toEqual($location_dns->lat);
    expect($device->location->lng)->toEqual($location_dns->lng);

    $device->location->lat = null;
    // won't be used if latitude is set
    $device->setLocation($location_encoded->location, true);
    expect($device->location->lat)->toEqual($location_api->lat);
    expect($device->location->lng)->toEqual($location_api->lng);
});
