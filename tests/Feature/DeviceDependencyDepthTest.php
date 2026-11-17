<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Models\Device;

test('attach parent updates depth', function () {
    $parent = Device::factory()->create();
    $child = Device::factory()->create();

    $child->parents()->attach($parent);

    expect($parent->fresh()->max_depth)->toBe(1);
    expect($child->fresh()->max_depth)->toBe(2);
});

test('attach child updates depth', function () {
    $parent = Device::factory()->create()->fresh();
    $child = Device::factory()->create();

    $parent->children()->attach($child);

    expect($parent->fresh()->max_depth)->toBe(1);
    expect($child->fresh()->max_depth)->toBe(2);
});

test('sync multiple parents updates depth', function () {
    $first = Device::factory()->create();
    $second = Device::factory()->create();
    $child = Device::factory()->create();

    $child->parents()->sync([$first->device_id, $second->device_id]);

    expect($first->fresh()->max_depth)->toBe(1);
    expect($second->fresh()->max_depth)->toBe(1);
    expect($child->fresh()->max_depth)->toBe(2);
    expect($child->parents()->pluck('devices.device_id')->all())->toEqualCanonicalizing([$first->device_id, $second->device_id]);
});

test('attach under existing child updates depth', function () {
    $root = Device::factory()->create();
    $middle = Device::factory()->create();
    $leaf = Device::factory()->create();

    $middle->parents()->attach($root);
    $leaf->parents()->attach($middle);

    expect($middle->fresh()->max_depth)->toBe(2);
    expect($leaf->fresh()->max_depth)->toBe(3);
});

test('attach second child keeps root depth', function () {
    $root = Device::factory()->create();
    $first = Device::factory()->create();
    $second = Device::factory()->create();

    $first->parents()->attach($root);
    $second->parents()->attach($root);

    expect($root->fresh()->max_depth)->toBe(1);
    expect($first->fresh()->max_depth)->toBe(2);
    expect($second->fresh()->max_depth)->toBe(2);
});

test('standalone device keeps zero depth', function () {
    $device = Device::factory()->create();

    expect($device->fresh()->max_depth)->toBe(0);
});

test('update max depth standalone', function () {
    $device = Device::factory()->create(['max_depth' => 5]);

    $device->updateMaxDepth();

    expect($device->fresh()->max_depth)->toBe(0);
});

test('update max depth root with children', function () {
    $root = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($root);
    $root->max_depth = 0;
    $root->save();

    $root->updateMaxDepth();

    expect($root->fresh()->max_depth)->toBe(1);
});

test('update max depth uses deepest parent', function () {
    $shallow = Device::factory()->create(['max_depth' => 1]);
    $deep = Device::factory()->create(['max_depth' => 4]);
    $child = Device::factory()->create();
    $child->parents()->attach([$shallow->device_id, $deep->device_id]);
    $deep->forceFill(['max_depth' => 4])->save();

    $child->fresh()->updateMaxDepth();

    expect($child->fresh()->max_depth)->toBe(5);
});

test('update max depth excludes parent', function () {
    $shallow = Device::factory()->create();
    $deep = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach([$shallow->device_id, $deep->device_id]);
    $shallow->forceFill(['max_depth' => 1])->save();
    $deep->forceFill(['max_depth' => 4])->save();

    $child->fresh()->updateMaxDepth($deep->device_id);
    expect($child->fresh()->max_depth)->toBe(2);

    $child->fresh()->updateMaxDepth($shallow->device_id);
    expect($child->fresh()->max_depth)->toBe(5);
});

test('update max depth excluding only parent', function () {
    $parent = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($parent);

    $child->fresh()->updateMaxDepth($parent->device_id);

    expect($child->fresh()->max_depth)->toBe(0);
});

test('validate standalone marks root with children', function () {
    $root = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($root);
    $root->forceFill(['max_depth' => 0])->save();

    $root->fresh()->validateStandalone();

    expect($root->fresh()->max_depth)->toBe(1);
});

test('validate standalone marks lone root standalone', function () {
    $device = Device::factory()->create(['max_depth' => 1]);

    $device->fresh()->validateStandalone();

    expect($device->fresh()->max_depth)->toBe(0);
});

test('validate standalone keeps root with children', function () {
    $root = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($root);

    $root->fresh()->validateStandalone();

    expect($root->fresh()->max_depth)->toBe(1);
});

test('validate standalone ignores deeper devices', function () {
    $root = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($root);

    $child->fresh()->validateStandalone();

    expect($child->fresh()->max_depth)->toBe(2);
});

test('deleting parent updates child depth', function () {
    $root = Device::factory()->create();
    $middle = Device::factory()->create();
    $leaf = Device::factory()->create();
    $middle->parents()->attach($root);
    $leaf->parents()->attach([$root->device_id, $middle->device_id]);
    expect($leaf->fresh()->max_depth)->toBe(3);

    $middle->fresh()->delete();

    expect($leaf->fresh()->max_depth)->toBe(2);
});

test('deleting only parent makes child standalone', function () {
    $parent = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($parent);

    $parent->fresh()->delete();

    expect($child->fresh()->max_depth)->toBe(0);
});

test('detach parent updates depth', function () {
    $parent = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($parent);

    $child->parents()->detach($parent);

    expect($parent->fresh()->max_depth)->toBe(0);
    expect($child->fresh()->max_depth)->toBe(0);
});

test('attach parent updates descendant depth', function () {
    $root = Device::factory()->create();
    $middle = Device::factory()->create();
    $leaf = Device::factory()->create();
    $leaf->parents()->attach($middle);

    $middle->parents()->attach($root);

    expect($middle->fresh()->max_depth)->toBe(2);
    expect($leaf->fresh()->max_depth)->toBe(3);
});

test('deleting child makes parent standalone', function () {
    $parent = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($parent);

    $child->fresh()->delete();

    expect($parent->fresh()->max_depth)->toBe(0);
});

test('detach all parents updates depth', function () {
    $first = Device::factory()->create();
    $second = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach([$first->device_id, $second->device_id]);

    $child->parents()->detach($child->parents()->allRelatedIds());

    expect($first->fresh()->max_depth)->toBe(0);
    expect($second->fresh()->max_depth)->toBe(0);
    expect($child->fresh()->max_depth)->toBe(0);
});

test('detach middle updates descendants', function () {
    $root = Device::factory()->create();
    $middle = Device::factory()->create();
    $leaf = Device::factory()->create();
    $middle->parents()->attach($root);
    $leaf->parents()->attach($middle);

    $middle->parents()->detach($root);

    expect($root->fresh()->max_depth)->toBe(0);
    expect($middle->fresh()->max_depth)->toBe(1);
    expect($leaf->fresh()->max_depth)->toBe(2);
});

test('dependency loop does not recurse forever', function () {
    $first = Device::factory()->create();
    $second = Device::factory()->create();
    $second->parents()->attach($first);

    $first->parents()->attach($second);

    expect($first->fresh()->max_depth)->toBeGreaterThan(0);
    expect($second->fresh()->max_depth)->toBeGreaterThan(0);
});

test('detach all children updates depth', function () {
    [$parent, $other_parent] = Device::factory()->count(2)->create();
    [$child1, $child2, $other_child] = Device::factory()->count(3)->create();
    $child1->parents()->attach($parent);
    $child2->parents()->attach([$parent->device_id, $other_parent->device_id]);
    $other_child->parents()->attach($other_parent);

    $parent->children()->detach($parent->children()->allRelatedIds());

    expect($parent->fresh()->max_depth)->toBe(0);
    expect($child1->fresh()->max_depth)->toBe(0);
    expect($other_parent->fresh()->max_depth)->toBe(1);
    expect($child2->fresh()->max_depth)->toBe(2);
    expect($other_child->fresh()->max_depth)->toBe(2);
});
