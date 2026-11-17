<?php

uses(\Illuminate\Database\Eloquent\Model::class);
use Illuminate\Database\Eloquent\Builder;
use \Illuminate\Database\Eloquent\Model;
use \LibreNMS\Tests\Unit\Models\Traits\FilterableDevice;
use \LibreNMS\Tests\Unit\Models\Traits\FilterableLocation;
use \LibreNMS\Tests\Unit\Models\Traits\FilterableRegion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::connection('testing_memory')->create('filterable_regions', function ($table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->timestamps();
    });

    Schema::connection('testing_memory')->create('filterable_locations', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('region_id')->nullable();
        $table->string('location')->nullable();
        $table->timestamps();
    });

    Schema::connection('testing_memory')->create('filterable_devices', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('location_id')->nullable();
        $table->string('hostname')->nullable();
        $table->string('os')->nullable();
        $table->integer('uptime')->nullable();
        $table->timestamps();
    });
});

/**
 * @param  array<string, string|int|null>  $deviceAttrs
 * @param  string|null  $location
 * @param  string|null  $region
 * @return void
 */
function device(array $deviceAttrs, ?string $location = null, ?string $region = null): void
{
    if ($location) {
        $locationAttrs = ['location' => $location];

        if ($region) {
            $reg = FilterableRegion::create(['name' => $region]);
            $locationAttrs['region_id'] = $reg->id;
        }

        $loc = FilterableLocation::create($locationAttrs);
        $deviceAttrs['location_id'] = $loc->id;
    }

    FilterableDevice::create($deviceAttrs);
}

test('filter validation rules contains all operator map keys', function () {
    $rules = FilterableDevice::filterValidationRules();

    expect($rules)->toHaveKey('filter');
    expect($rules)->toHaveKey('filter.*');
    expect($rules)->toHaveKey('filter.*.*');
});

test('filter validation rules rejects unknown operator', function () {
    $rules = FilterableDevice::filterValidationRules();
    $closure = collect($rules['filter.*'])->first(fn ($r) => $r instanceof \Closure);

    $failed = false;
    $closure('filter.hostname', ['bogus_op' => 'value'], function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('filter validation rules accepts known operator', function () {
    $rules = FilterableDevice::filterValidationRules();
    $closure = collect($rules['filter.*'])->first(fn ($r) => $r instanceof \Closure);

    $failed = false;
    $closure('filter.hostname', ['eq' => 'router1'], function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('eq', function () {
    device(['hostname' => 'router1', 'os' => 'ios']);
    device(['hostname' => 'router2', 'os' => 'eos']);

    $results = FilterableDevice::applyFilters(['hostname' => ['eq' => 'router1']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('neq', function () {
    device(['hostname' => 'router1']);
    device(['hostname' => 'router2']);

    $results = FilterableDevice::applyFilters(['hostname' => ['neq' => 'router1']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router2');
});

test('gt', function () {
    device(['hostname' => 'a', 'uptime' => 100]);
    device(['hostname' => 'b', 'uptime' => 200]);
    device(['hostname' => 'c', 'uptime' => 300]);

    $results = FilterableDevice::applyFilters(['uptime' => ['gt' => 200]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('c');
});

test('gte', function () {
    device(['hostname' => 'a', 'uptime' => 100]);
    device(['hostname' => 'b', 'uptime' => 200]);

    $results = FilterableDevice::applyFilters(['uptime' => ['gte' => 200]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('b');
});

test('lt', function () {
    device(['hostname' => 'a', 'uptime' => 100]);
    device(['hostname' => 'b', 'uptime' => 200]);

    $results = FilterableDevice::applyFilters(['uptime' => ['lt' => 200]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('a');
});

test('lte', function () {
    device(['hostname' => 'a', 'uptime' => 100]);
    device(['hostname' => 'b', 'uptime' => 200]);

    $results = FilterableDevice::applyFilters(['uptime' => ['lte' => 100]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('a');
});

test('contains', function () {
    device(['hostname' => 'core-router-01']);
    device(['hostname' => 'edge-switch-01']);

    $results = FilterableDevice::applyFilters(['hostname' => ['contains' => 'router']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-router-01');
});

test('not contains', function () {
    device(['hostname' => 'core-router-01']);
    device(['hostname' => 'edge-switch-01']);

    $results = FilterableDevice::applyFilters(['hostname' => ['not_contains' => 'router']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('edge-switch-01');
});

test('starts with', function () {
    device(['hostname' => 'core-router-01']);
    device(['hostname' => 'edge-switch-01']);

    $results = FilterableDevice::applyFilters(['hostname' => ['starts_with' => 'core']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-router-01');
});

test('ends with', function () {
    device(['hostname' => 'core-router-01']);
    device(['hostname' => 'edge-switch-02']);

    $results = FilterableDevice::applyFilters(['hostname' => ['ends_with' => '01']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-router-01');
});

test('is empty matches null', function () {
    device(['hostname' => 'router1', 'os' => null]);
    device(['hostname' => 'router2', 'os' => 'ios']);

    $results = FilterableDevice::applyFilters(['os' => ['is_empty' => null]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('is empty matches empty string', function () {
    device(['hostname' => 'router1', 'os' => '']);
    device(['hostname' => 'router2', 'os' => 'ios']);

    $results = FilterableDevice::applyFilters(['os' => ['is_empty' => null]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('is not empty', function () {
    device(['hostname' => 'router1', 'os' => null]);
    device(['hostname' => 'router2', 'os' => '']);
    device(['hostname' => 'router3', 'os' => 'ios']);

    $results = FilterableDevice::applyFilters(['os' => ['is_not_empty' => null]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router3');
});

test('in', function () {
    device(['hostname' => 'router1', 'os' => 'ios']);
    device(['hostname' => 'router2', 'os' => 'eos']);
    device(['hostname' => 'router3', 'os' => 'junos']);

    $results = FilterableDevice::applyFilters(['os' => ['in' => ['ios', 'eos']]])->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('os')->all())->toEqualCanonicalizing(['ios', 'eos']);
});

test('not in', function () {
    device(['hostname' => 'router1', 'os' => 'ios']);
    device(['hostname' => 'router2', 'os' => 'eos']);
    device(['hostname' => 'router3', 'os' => 'junos']);

    $results = FilterableDevice::applyFilters(['os' => ['not_in' => ['ios', 'eos']]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->os)->toEqual('junos');
});

test('in with comma separated string', function () {
    device(['hostname' => 'router1', 'os' => 'ios']);
    device(['hostname' => 'router2', 'os' => 'eos']);
    device(['hostname' => 'router3', 'os' => 'junos']);

    $results = FilterableDevice::applyFilters(['os' => ['in' => 'ios,eos']])->get();

    expect($results)->toHaveCount(2);
});

test('in operator with empty values', function () {
    device(['hostname' => 'router1', 'os' => 'ios']);

    $results = FilterableDevice::applyFilters(['os' => ['in' => []]])->get();
    expect($results)->toHaveCount(0);

    $results = FilterableDevice::applyFilters(['os' => ['in' => '']])->get();
    expect($results)->toHaveCount(0);
});

test('multiple fields apply and logic', function () {
    device(['hostname' => 'core-rt-01', 'os' => 'ios']);
    device(['hostname' => 'edge-rt-01', 'os' => 'ios']);
    device(['hostname' => 'core-rt-01', 'os' => 'junos']);

    $results = FilterableDevice::applyFilters([
        'hostname' => ['contains' => 'core'],
        'os' => ['eq' => 'ios'],
    ])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-rt-01');
    expect($results->first()->os)->toEqual('ios');
});

test('relation eq', function () {
    device(['hostname' => 'router1'], 'London');
    device(['hostname' => 'router2'], 'Paris');

    $results = FilterableDevice::applyFilters(['location.location' => ['eq' => 'London']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('relation contains', function () {
    device(['hostname' => 'router1'], 'London DC');
    device(['hostname' => 'router2'], 'Paris DC');

    $results = FilterableDevice::applyFilters(['location.location' => ['contains' => 'London']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('relation neq', function () {
    device(['hostname' => 'router1'], 'London');
    device(['hostname' => 'router2'], 'Paris');

    $results = FilterableDevice::applyFilters(['location.location' => ['neq' => 'London']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router2');
});

test('deeply nested relation filtering', function () {
    device(['hostname' => 'uk-router'], 'London', 'Europe');
    device(['hostname' => 'us-router']);

    $results = FilterableDevice::applyFilters([
        'location.region.name' => ['eq' => 'Europe'],
    ])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('uk-router');
});

test('relation neq includes records with no relation', function () {
    device(['hostname' => 'lon-gw'], 'London');
    device(['hostname' => 'par-gw'], 'Paris');
    device(['hostname' => 'orphan-gw', 'location_id' => null]);

    $results = FilterableDevice::applyFilters([
        'location.location' => ['neq' => 'London'],
    ])->get();

    expect($results)->toHaveCount(2);
    $hostnames = $results->pluck('hostname')->all();
    expect($hostnames)->toContain('par-gw');
    expect($hostnames)->toContain('orphan-gw');
});

test('relation is empty finds records without relationship', function () {
    device(['hostname' => 'connected-gw'], 'London');
    device(['hostname' => 'unassigned-gw', 'location_id' => null]);

    $results = FilterableDevice::applyFilters([
        'location.location' => ['is_empty' => '1'],
    ])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('unassigned-gw');
});

test('relation is not empty finds records with relationship', function () {
    device(['hostname' => 'connected-gw'], 'Paris');
    device(['hostname' => 'unassigned-gw', 'location_id' => null]);

    $results = FilterableDevice::applyFilters([
        'location.location' => ['is_not_empty' => '1'],
    ])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('connected-gw');
});

test('apply filter search or logic', function () {
    device(['hostname' => 'core-router', 'os' => 'ios']);
    device(['hostname' => 'edge-switch', 'os' => 'eos']);
    device(['hostname' => 'spine-leaf',  'os' => 'junos']);

    // filterSearch on Device searches both hostname and os
    $results = FilterableDevice::applyFilters(['search' => ['contains' => 'ios']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-router');
});

test('apply filter search not contains and logic', function () {
    device(['hostname' => 'core-router', 'os' => 'ios']);
    device(['hostname' => 'edge-switch', 'os' => 'eos']);

    $results = FilterableDevice::applyFilters(['search' => ['not_contains' => 'ios']])->get();

    // AND logic: hostname NOT LIKE AND os NOT LIKE — only edge-switch qualifies
    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('edge-switch');
});

test('non filterable field is ignored', function () {
    device(['hostname' => 'router1']);

    $results = FilterableDevice::applyFilters(['secret' => ['eq' => 'value']])->get();

    expect($results)->toHaveCount(1);
});

test('unknown operator is ignored', function () {
    device(['hostname' => 'router1']);

    $results = FilterableDevice::applyFilters(['hostname' => ['bogus' => 'value']])->get();

    expect($results)->toHaveCount(1);
});

test('non array operators are ignored', function () {
    device(['hostname' => 'router1']);

    /** @phpstan-ignore-next-line */
    $results = FilterableDevice::applyFilters(['hostname' => 'not-an-array'])->get();

    expect($results)->toHaveCount(1);
});

// ---------------------------------------------------------------------------
// Inline test doubles
// ---------------------------------------------------------------------------
/**
 * @property int $id
 * @property string|null $region
 */
class FilterableRegion extends Model
{
}

/**
 * @property int $id
 * @property string|null $location
 * @property int|null $region_id
 */
class FilterableLocation extends Model
{
    /**
     * @return BelongsTo<FilterableRegion, $this>
     */
    function region(): BelongsTo
    {
        return $this->belongsTo(FilterableRegion::class, 'region_id');
    }
}
/**
 * @property int $id
 * @property string|null $hostname
 * @property string|null $os
 * @property int|null $uptime
 * @property int|null $location_id
 */
class FilterableDevice extends Model
{
    uses(\App\Models\Traits\Filterable::class);
    
    /**
     * @return BelongsTo<FilterableLocation, $this>
     */
    function location(): BelongsTo
    {
        return $this->belongsTo(FilterableLocation::class, 'location_id');
    }

    /**
     * @param  Builder<static>  $query
     * @param  scalar|scalar[]  $value
     * @param  array{operator?: string, wildcard?: string, not?: bool, null?: bool, set?: bool}  $config
     * @return void
     */
    function filterSearch(Builder $query, mixed $value, array $config): void
    {
        $this->applyFilterSearch(['hostname', 'os'], $query, $value, $config);
    }
}