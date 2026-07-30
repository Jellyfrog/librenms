<?php

/**
 * ComponentTest.php
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
 * @copyright  2020 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Models\ComponentPref;
use App\Models\ComponentStatusLog;
use Illuminate\Support\Str;
use LibreNMS\Component;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('delete component', function (): void {
    $target = App\Models\Component::factory()->create();
    /** @var App\Models\Component $target */
    expect(App\Models\Component::where('id', $target->id)->exists())->toBeTrue('Failed to create component, this shouldn\'t happen');

    $component = new Component();
    $component->deleteComponent($target->id);

    expect(App\Models\Component::where('id', $target->id)->exists())->toBeFalse('deleteComponent failed to delete the component.');
});

test('get components empty', function (): void {
    expect((new Component())->getComponents(43))->toEqual([]);
});

test('get components options type', function (): void {
    $target = App\Models\Component::factory()->create();
    /** @var App\Models\Component $target */
    $component = new Component();

    $actual = $component->getComponents($target->device_id, ['type' => $target->type]);

    expect($actual)->toHaveCount(1);
    expect($actual)->toHaveKey($target->device_id);
    expect($actual)->toEqual(buildExpected($target));
});

test('get components options filter not ignore', function (): void {
    App\Models\Component::factory()->create(['device_id' => 1, 'ignore' => 1]);
    $target = App\Models\Component::factory()->times(2)->create(['device_id' => 1, 'ignore' => 0]);
    /** @var Illuminate\Support\Collection $target */
    $component = new Component();

    $actual = $component->getComponents(1, ['filter' => ['ignore' => ['=', 0]]]);

    expect($actual)->toEqual(buildExpected($target));
});

test('get components options complex', function (): void {
    App\Models\Component::factory()->create(['label' => 'Search Phrase']);
    App\Models\Component::factory()->times(2)->create(['label' => 'Something Else']);
    $target = App\Models\Component::factory()->times(2)->create(['label' => 'Search Phrase']);
    /** @var Illuminate\Support\Collection $target */
    App\Models\Component::factory()->create(['label' => 'Search Phrase']);
    $component = new Component();

    $options = [
        'filter' => ['label' => ['like', 'Search Phrase']],
        'sort' => 'id desc',
        'limit' => [1, 2],
    ];
    $actual = $component->getComponents(null, $options);

    expect($actual)->toEqual(buildExpected($target->reverse()->values()));
});

test('get first component id', function (): void {
    $input = [
        1 => [37 => [], 14 => []],
        2 => [19 => []],
    ];
    $component = new Component();
    expect($component->getFirstComponentID($input, 2))->toEqual(19);
    expect($component->getFirstComponentID($input[1]))->toEqual(37);
});

test('get component count', function (): void {
    App\Models\Component::factory()->times(2)->create(['device_id' => 1, 'type' => 'three']);
    App\Models\Component::factory()->create(['device_id' => 2, 'type' => 'three']);
    App\Models\Component::factory()->create(['device_id' => 2, 'type' => 'one']);

    $component = new Component();
    expect($component->getComponentCount())->toEqual(['three' => 3, 'one' => 1]);
    expect($component->getComponentCount(1))->toEqual(['three' => 2]);
    expect($component->getComponentCount(2))->toEqual(['three' => 1, 'one' => 1]);
});

test('set component prefs', function (): void {
    // Nightmare function, no where near exhaustive
    $base = App\Models\Component::factory()->create();
    /** @var App\Models\Component $base */
    $component = new Component();

    Log::shouldReceive('event')->withArgs(["Component: $base->type($base->id). Attribute: null_val, was added with value: ", $base->device_id, 'component', 3, $base->id]);
    $nullVal = buildExpected($base)[$base->device_id];
    $nullVal[$base->id]['null_val'] = null;
    $component->setComponentPrefs($base->device_id, $nullVal);
    expect(ComponentPref::where(['component' => $base->id, 'attribute' => 'null_val'])->first()->value)->toEqual('');

    Log::shouldReceive('event')->withArgs(["Component $base->id has been modified: label => new label", $base->device_id, 'component', 3, $base->id]);
    Log::shouldReceive('event')->withArgs(["Component: $base->type($base->id). Attribute: thirty, was added with value: 30", $base->device_id, 'component', 3, $base->id]);
    Log::shouldReceive('event')->withArgs(["Component: $base->type($base->id). Attribute: json, was added with value: {\"json\":\"array\"}", $base->device_id, 'component', 3, $base->id]);
    Log::shouldReceive('event')->withArgs(["Component: $base->type($base->id). Attribute: null_val, was deleted.", $base->device_id, 'component', 4]);
    $multiple = buildExpected($base)[$base->device_id];
    $multiple[$base->id]['label'] = 'new label';
    $multiple[$base->id]['thirty'] = 30;
    $multiple[$base->id]['json'] = json_encode(['json' => 'array']);
    $component->setComponentPrefs($base->device_id, $multiple);

    Log::shouldReceive('event')->withArgs(["Component $base->id has been modified: label => ", $base->device_id, 'component', 3, $base->id]);
    Log::shouldReceive('event')->withArgs(["Component: $base->type($base->id). Attribute: thirty, was deleted.", $base->device_id, 'component', 4]);
    Log::shouldReceive('event')->withArgs(["Component: $base->type($base->id). Attribute: json, was deleted.", $base->device_id, 'component', 4]);
    $uc = App\Models\Component::find($base->id);
    expect($uc->label)->toEqual('new label');
    expect($uc->prefs->where('attribute', 'thirty')->first()->value)->toEqual(30);
    expect($uc->prefs->where('attribute', 'json')->first()->value)->toEqual($multiple[$base->id]['json']);

    Log::shouldReceive('event')->times(0);
    $remove = buildExpected($base)[$base->device_id];
    $component->setComponentPrefs($base->device_id, $remove);
    expect(ComponentPref::where('component', $base->id)->exists())->toBeFalse();
});

test('create component', function (): void {
    $device_id = random_int(1, 32);
    $type = Str::random(9);
    $component = (new Component())->createComponent($device_id, $type);

    expect($component)->toHaveCount(1);
    $component_id = array_key_first($component);

    $expected = ['type' => $type, 'label' => '', 'status' => 0, 'ignore' => 0, 'disabled' => 0, 'error' => ''];

    expect($component)->toEqual([$component_id => $expected]);

    $fromDb = App\Models\Component::find($component_id);
    expect($fromDb->device_id)->toEqual($device_id);
    expect($fromDb->type)->toEqual($type);

    $log = $fromDb->logs->first();
    expect(0)->toEqual($log->status);
    expect('Component Created')->toEqual($log->message);
});

test('get component status log', function (): void {
    // invalid id fails
    $component = new Component();

    expect($component->createStatusLogEntry(434242, 0, 'failed'))->toEqual(0, 'incorrectly added log');

    $message = Str::random(8);
    $model = App\Models\Component::factory()->create();
    /** @var App\Models\Component $model */
    $log_id = $component->createStatusLogEntry($model->id, 1, $message);
    $this->assertNotEquals(0, $log_id, ' failed to create log');

    $log = ComponentStatusLog::find($log_id);
    expect($log->status)->toEqual(1);
    expect($log->message)->toEqual($message);
});

function buildExpected($target)
{
    $collection = $target instanceof App\Models\Component ? collect([$target]) : $target;

    return $collection->groupBy('device_id')->map(fn ($group) => $group->keyBy('id')->map(function ($model) {
        $base = ['type' => null, 'label' => null, 'status' => 0, 'ignore' => 0, 'disabled' => 0, 'error' => null];
        $merge = $model->toArray();
        unset($merge['device_id'], $merge['id']);

        return array_merge($base, $merge);
    }))->toArray();
}
