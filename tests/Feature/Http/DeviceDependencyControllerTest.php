<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\User;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function user(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

test('admin can view page', function () {
    $this->actingAs(admin())
        ->get(route('device-dependencies.index'))
        ->assertOk()
        ->assertSee('x-data="deviceDependencies"', false)
        ->assertSee('id="hostdeps"', false);
});

test('user cannot view page', function () {
    $this->actingAs(user())
        ->get(route('device-dependencies.index'))
        ->assertForbidden();
});

test('table lists devices with parents', function () {
    $parent = Device::factory()->create(['hostname' => 'dep-parent.example.com']);
    $child = Device::factory()->create(['hostname' => 'dep-child.example.com']);
    $child->parents()->attach($parent);

    $response = $this->actingAs(admin())
        ->postJson(route('table.device-dependencies'), ['searchPhrase' => 'dep-', 'rowCount' => -1])
        ->assertOk();

    $rows = $response->json('rows');
    expect($rows)->toBeArray();
    $rows = collect($rows)->keyBy('device_id');
    expect($rows[$parent->device_id]['parents'])->toBe(__('None'));
    expect($rows[$parent->device_id]['parents_json'])->toBe('[]');
    $this->assertStringContainsString('dep-parent.example.com', $rows[$child->device_id]['parents']);
    expect(json_decode($rows[$child->device_id]['parents_json'], true))->toBe([['id' => $parent->device_id, 'text' => $parent->display]]);
});

test('table search matches parent hostname', function () {
    $parent = Device::factory()->create(['hostname' => 'uniq-parent-search.example.com']);
    $child = Device::factory()->create(['hostname' => 'other-child.example.com']);
    $child->parents()->attach($parent);

    $response = $this->actingAs(admin())
        ->postJson(route('table.device-dependencies'), ['searchPhrase' => 'uniq-parent-search'])
        ->assertOk();

    $rows = $response->json('rows');
    expect($rows)->toBeArray();
    expect(array_column($rows, 'device_id'))->toEqualCanonicalizing([$parent->device_id, $child->device_id]);
});

test('table sorts by parent hostname', function () {
    $parentA = Device::factory()->create(['hostname' => 'sortdep-a-parent.example.com']);
    $parentB = Device::factory()->create(['hostname' => 'sortdep-b-parent.example.com']);
    $childOfB = Device::factory()->create(['hostname' => 'sortdep-c-child.example.com']);
    $childOfA = Device::factory()->create(['hostname' => 'sortdep-d-child.example.com']);
    $childOfB->parents()->attach($parentB);
    $childOfA->parents()->attach($parentA);

    $sorted = fn (string $direction) => array_column($this->actingAs(admin())
        ->postJson(route('table.device-dependencies'), ['searchPhrase' => 'sortdep-', 'rowCount' => -1, 'sort' => ['parents' => $direction]])
        ->assertOk()
        ->json('rows'), 'device_id');

    expect($sorted('asc'))->toBe([$parentA->device_id, $parentB->device_id, $childOfA->device_id, $childOfB->device_id]);
    expect($sorted('desc'))->toBe([$childOfB->device_id, $childOfA->device_id, $parentB->device_id, $parentA->device_id]);
});

test('user cannot view table', function () {
    $this->actingAs(user())
        ->postJson(route('table.device-dependencies'))
        ->assertForbidden();
});

test('update sets parents for devices', function () {
    [$parent1, $parent2, $old_parent] = Device::factory()->count(3)->create();
    [$child1, $child2] = Device::factory()->count(2)->create();
    $child1->parents()->attach($old_parent);

    $this->actingAs(admin())
        ->putJson(route('device-dependencies.update'), [
            'device_ids' => [$child1->device_id, $child2->device_id],
            'parent_ids' => [$parent1->device_id, $parent2->device_id],
        ])
        ->assertOk();

    $expected = [$parent1->device_id, $parent2->device_id];
    expect($child1->parents()->pluck('devices.device_id')->all())->toEqualCanonicalizing($expected);
    expect($child2->parents()->pluck('devices.device_id')->all())->toEqualCanonicalizing($expected);
});

test('update without parents clears dependencies', function () {
    $parent = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($parent);

    $this->actingAs(admin())
        ->putJson(route('device-dependencies.update'), ['device_ids' => [$child->device_id]])
        ->assertOk()
        ->assertJson(['message' => 'Device dependencies have been removed']);

    expect($child->parents()->count())->toBe(0);
});

test('update rejects self dependency and unknown devices', function () {
    $device = Device::factory()->create();

    $this->actingAs(admin())
        ->putJson(route('device-dependencies.update'), [
            'device_ids' => [$device->device_id],
            'parent_ids' => [$device->device_id, 999999999],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_ids.0', 'parent_ids.1']);

    $this->actingAs(admin())
        ->putJson(route('device-dependencies.update'), ['parent_ids' => [$device->device_id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['device_ids']);

    expect($device->parents()->count())->toBe(0);
});

test('user cannot update dependencies', function () {
    $parent = Device::factory()->create();
    $child = Device::factory()->create();

    $this->actingAs(user())
        ->putJson(route('device-dependencies.update'), [
            'device_ids' => [$child->device_id],
            'parent_ids' => [$parent->device_id],
        ])
        ->assertForbidden();

    expect($child->parents()->count())->toBe(0);
});

test('destroy clears children of parents', function () {
    [$parent, $other_parent] = Device::factory()->count(2)->create();
    [$child1, $child2, $other_child] = Device::factory()->count(3)->create();
    $child1->parents()->attach($parent);
    $child2->parents()->attach([$parent->device_id, $other_parent->device_id]);
    $other_child->parents()->attach($other_parent);

    $this->actingAs(admin())
        ->deleteJson(route('device-dependencies.destroy'), ['parent_ids' => [$parent->device_id]])
        ->assertOk();

    expect($parent->children()->count())->toBe(0);
    expect($child2->parents()->pluck('devices.device_id')->all())->toEqual([$other_parent->device_id]);
    expect($other_child->parents()->pluck('devices.device_id')->all())->toEqual([$other_parent->device_id]);

    expect($parent->fresh()->max_depth)->toBe(0);
    expect($child1->fresh()->max_depth)->toBe(0);
    expect($other_parent->fresh()->max_depth)->toBe(1);
    expect($child2->fresh()->max_depth)->toBe(2);
    expect($other_child->fresh()->max_depth)->toBe(2);
});

test('user cannot destroy dependencies', function () {
    $parent = Device::factory()->create();
    $child = Device::factory()->create();
    $child->parents()->attach($parent);

    $this->actingAs(user())
        ->deleteJson(route('device-dependencies.destroy'), ['parent_ids' => [$parent->device_id]])
        ->assertForbidden();

    expect($child->parents()->count())->toBe(1);
});