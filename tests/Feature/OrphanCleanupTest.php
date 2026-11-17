<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Link;
use App\Models\Mempool;
use App\Models\Port;
use App\Models\Processor;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('deleting a device deletes its local links', function () {
    $device = Device::factory()->create();
    $link = Link::factory()->create([
        'local_device_id' => $device->device_id,
        'local_port_id' => null,
        'remote_device_id' => 0,
    ]);

    $device->delete();

    $this->assertDatabaseMissing('links', ['id' => $link->id]);
});

test('deleting a device deletes links pointing at it', function () {
    $local = Device::factory()->create();
    $remote = Device::factory()->create();
    $link = Link::factory()->create([
        'local_device_id' => $local->device_id,
        'local_port_id' => null,
        'remote_device_id' => $remote->device_id,
    ]);

    $remote->delete();

    $this->assertDatabaseMissing('links', ['id' => $link->id]);
});

test('deleting a device leaves unrelated links alone', function () {
    $device = Device::factory()->create();
    $other = Device::factory()->create();
    $keep = Link::factory()->create([
        'local_device_id' => $other->device_id,
        'local_port_id' => null,
        'remote_device_id' => 0,
    ]);

    $device->delete();

    $this->assertDatabaseHas('links', ['id' => $keep->id]);
});

test('deleting a port still deletes its links', function () {
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id]);
    $link = Link::factory()->create([
        'local_device_id' => $device->device_id,
        'local_port_id' => $port->port_id,
        'remote_device_id' => 0,
    ]);

    $port->delete();

    $this->assertDatabaseMissing('links', ['id' => $link->id]);
});

test('orphan cleanup does not read the devices table', function () {
    Link::factory()->create([
        'local_device_id' => 99999,
        'local_port_id' => null,
        'remote_device_id' => 0,
    ]);

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    Link::deleteOrphans();

    $deletes = array_values(array_filter($queries, fn ($sql) => str_starts_with(strtolower(ltrim($sql)), 'delete')));

    expect($deletes)->not->toBeEmpty('expected a delete statement');
    foreach ($deletes as $sql) {
        $this->assertStringNotContainsString('devices', $sql, "delete statement must not lock devices: $sql");
    }
});

test('orphan cleanup removes links whose device is gone', function () {
    $link = Link::factory()->create([
        'local_device_id' => 99999,
        'local_port_id' => null,
        'remote_device_id' => 0,
    ]);

    Link::deleteOrphans();

    $this->assertDatabaseMissing('links', ['id' => $link->id]);
});

test('orphan cleanup keeps links whose device exists', function () {
    $device = Device::factory()->create();
    $link = Link::factory()->create([
        'local_device_id' => $device->device_id,
        'local_port_id' => null,
        'remote_device_id' => 0,
    ]);

    Link::deleteOrphans();

    $this->assertDatabaseHas('links', ['id' => $link->id]);
});

test('processor orphan cleanup does not read the devices table', function () {
    Processor::factory()->create(['device_id' => 99999]);

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    Processor::deleteOrphans();

    $deletes = array_values(array_filter($queries, fn ($sql) => str_starts_with(strtolower(ltrim($sql)), 'delete')));

    expect($deletes)->not->toBeEmpty('expected a delete statement');
    foreach ($deletes as $sql) {
        $this->assertStringNotContainsString('devices', $sql, "delete statement must not lock devices: $sql");
    }
});

test('processor orphan cleanup removes rows whose device is gone', function () {
    $orphan = Processor::factory()->create(['device_id' => 99999]);
    $kept = Processor::factory()->create();

    Processor::deleteOrphans();

    $this->assertDatabaseMissing('processors', ['processor_id' => $orphan->processor_id]);
    $this->assertDatabaseHas('processors', ['processor_id' => $kept->processor_id]);
});

test('a device related model sweeps orphans without declaring the trait', function () {
    $orphan = Mempool::factory()->create(['device_id' => 99999]);
    $kept = Mempool::factory()->create();

    Mempool::deleteOrphans();

    $this->assertDatabaseMissing('mempools', ['mempool_id' => $orphan->mempool_id]);
    $this->assertDatabaseHas('mempools', ['mempool_id' => $kept->mempool_id]);
});

test('a device related model orphan cleanup does not read the devices table', function () {
    Mempool::factory()->create(['device_id' => 99999]);

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    Mempool::deleteOrphans();

    $deletes = array_values(array_filter($queries, fn ($sql) => str_starts_with(strtolower(ltrim($sql)), 'delete')));

    expect($deletes)->not->toBeEmpty('expected a delete statement');
    foreach ($deletes as $sql) {
        $this->assertStringNotContainsString('devices', $sql, "delete statement must not lock devices: $sql");
    }
});