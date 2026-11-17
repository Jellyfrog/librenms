<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use LibreNMS\Util\Graph;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('device graphs are limited to the requested type', function () {
    LibrenmsConfig::set('graph_types.device.test_device_graph', ['section' => 'test']);
    LibrenmsConfig::set('graph_types.port.test_port_graph', ['section' => 'test']);
    LibrenmsConfig::set('graph_types.device.test_unused_graph', ['section' => 'test']);

    $device = Device::factory()->create();
    $device->graphs()->createMany([['graph' => 'test_device_graph'], ['graph' => 'test_port_graph']]);

    $deviceTypes = Graph::getSubtypes('device', $device);
    expect($deviceTypes)->toContain('test_device_graph');
    expect($deviceTypes)->not->toContain('test_port_graph');
    expect($deviceTypes)->not->toContain('test_unused_graph');

    $portTypes = Graph::getSubtypes('port', $device);
    expect($portTypes)->toContain('test_port_graph');
    expect($portTypes)->not->toContain('test_device_graph');
});

test('type without configured graphs only lists its own subtypes', function () {
    LibrenmsConfig::set('graph_types.device.test_device_graph', ['section' => 'test']);

    $device = Device::factory()->create();
    $device->graphs()->create(['graph' => 'test_device_graph']);

    expect(Graph::getSubtypes('nosuchtype', $device))->not->toContain('test_device_graph');
});