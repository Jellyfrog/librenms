<?php

use App\Facades\LibrenmsConfig;
use LibreNMS\Data\Store\Kafka;

uses(LibreNMS\Tests\TestCase::class);

beforeEach(function (): void {
    LibrenmsConfig::set('kafka.enable', true);
    LibrenmsConfig::set('kafka.broker.list', 'localhost:9092');
    LibrenmsConfig::set('kafka.topic', 'librenms');
    LibrenmsConfig::set('kafka.idempotence', false);
    LibrenmsConfig::set('kafka.buffer.max.message', 10);
    LibrenmsConfig::set('kafka.batch.max.message', 25);
    LibrenmsConfig::set('kafka.linger.ms', 5000);
    LibrenmsConfig::set('kafka.request.required.acks', 0);
});

test('data push to kafka', function (): void {
    // by name, so the test does not need php-rdkafka-ffi and librdkafka
    $producer = Mockery::mock(\RdKafka\Producer::class);
    $producer->shouldReceive('newTopic')->once();
    $producer->shouldReceive('getOutQLen')->andReturn(0);

    /** @var RdKafka\Producer $producer */
    $producer = $producer;
    $kafka = new Kafka($producer);

    $device = ['device_id' => 1, 'hostname' => 'testhost'];
    $measurement = 'excluded_measurement';
    $tags = ['ifName' => 'testifname', 'type' => 'testtype'];
    $fields = ['ifIn' => 234234, 'ifOut' => 53453];

    $metadata = [
        'device' => $device,
    ];
    $kafka->write($measurement, $fields, $tags, $metadata);
});

afterEach(function (): void {
    LibrenmsConfig::set('kafka.enable', false);
});
