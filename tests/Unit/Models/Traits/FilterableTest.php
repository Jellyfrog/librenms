<?php

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

uses(LibreNMS\Tests\TestCase::class);

beforeEach(function (): void {
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
function createFilterableDevice(array $deviceAttrs, ?string $location = null, ?string $region = null): void
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

// -------------------------------------------------------------------------
// filterValidationRules
// -------------------------------------------------------------------------

test('filter validation rules contains all operator map keys', function (): void {
    $rules = FilterableDevice::filterValidationRules();

    expect($rules)->toHaveKey('filter');
    expect($rules)->toHaveKey('filter.*');
    expect($rules)->toHaveKey('filter.*.*');
});

test('filter validation rules rejects unknown operator', function (): void {
    $rules = FilterableDevice::filterValidationRules();
    $closure = collect($rules['filter.*'])->first(fn ($r) => $r instanceof \Closure);

    $failed = false;
    $closure('filter.hostname', ['bogus_op' => 'value'], function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeTrue();
});

test('filter validation rules accepts known operator', function (): void {
    $rules = FilterableDevice::filterValidationRules();
    $closure = collect($rules['filter.*'])->first(fn ($r) => $r instanceof \Closure);

    $failed = false;
    $closure('filter.hostname', ['eq' => 'router1'], function () use (&$failed): void {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

// -------------------------------------------------------------------------
// Equality operators
// -------------------------------------------------------------------------

test('eq', function (): void {
    createFilterableDevice(['hostname' => 'router1', 'os' => 'ios']);
    createFilterableDevice(['hostname' => 'router2', 'os' => 'eos']);

    $results = FilterableDevice::applyFilters(['hostname' => ['eq' => 'router1']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('neq', function (): void {
    createFilterableDevice(['hostname' => 'router1']);
    createFilterableDevice(['hostname' => 'router2']);

    $results = FilterableDevice::applyFilters(['hostname' => ['neq' => 'router1']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router2');
});

// -------------------------------------------------------------------------
// Comparison operators
// -------------------------------------------------------------------------

test('gt', function (): void {
    createFilterableDevice(['hostname' => 'a', 'uptime' => 100]);
    createFilterableDevice(['hostname' => 'b', 'uptime' => 200]);
    createFilterableDevice(['hostname' => 'c', 'uptime' => 300]);

    $results = FilterableDevice::applyFilters(['uptime' => ['gt' => 200]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('c');
});

test('gte', function (): void {
    createFilterableDevice(['hostname' => 'a', 'uptime' => 100]);
    createFilterableDevice(['hostname' => 'b', 'uptime' => 200]);

    $results = FilterableDevice::applyFilters(['uptime' => ['gte' => 200]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('b');
});

test('lt', function (): void {
    createFilterableDevice(['hostname' => 'a', 'uptime' => 100]);
    createFilterableDevice(['hostname' => 'b', 'uptime' => 200]);

    $results = FilterableDevice::applyFilters(['uptime' => ['lt' => 200]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('a');
});

test('lte', function (): void {
    createFilterableDevice(['hostname' => 'a', 'uptime' => 100]);
    createFilterableDevice(['hostname' => 'b', 'uptime' => 200]);

    $results = FilterableDevice::applyFilters(['uptime' => ['lte' => 100]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('a');
});

// -------------------------------------------------------------------------
// String pattern operators
// -------------------------------------------------------------------------

test('contains', function (): void {
    createFilterableDevice(['hostname' => 'core-router-01']);
    createFilterableDevice(['hostname' => 'edge-switch-01']);

    $results = FilterableDevice::applyFilters(['hostname' => ['contains' => 'router']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-router-01');
});

test('not contains', function (): void {
    createFilterableDevice(['hostname' => 'core-router-01']);
    createFilterableDevice(['hostname' => 'edge-switch-01']);

    $results = FilterableDevice::applyFilters(['hostname' => ['not_contains' => 'router']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('edge-switch-01');
});

test('starts with', function (): void {
    createFilterableDevice(['hostname' => 'core-router-01']);
    createFilterableDevice(['hostname' => 'edge-switch-01']);

    $results = FilterableDevice::applyFilters(['hostname' => ['starts_with' => 'core']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-router-01');
});

test('ends with', function (): void {
    createFilterableDevice(['hostname' => 'core-router-01']);
    createFilterableDevice(['hostname' => 'edge-switch-02']);

    $results = FilterableDevice::applyFilters(['hostname' => ['ends_with' => '01']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-router-01');
});

// -------------------------------------------------------------------------
// Null / empty operators
// -------------------------------------------------------------------------

test('is empty matches null', function (): void {
    createFilterableDevice(['hostname' => 'router1', 'os' => null]);
    createFilterableDevice(['hostname' => 'router2', 'os' => 'ios']);

    $results = FilterableDevice::applyFilters(['os' => ['is_empty' => null]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('is empty matches empty string', function (): void {
    createFilterableDevice(['hostname' => 'router1', 'os' => '']);
    createFilterableDevice(['hostname' => 'router2', 'os' => 'ios']);

    $results = FilterableDevice::applyFilters(['os' => ['is_empty' => null]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('is not empty', function (): void {
    createFilterableDevice(['hostname' => 'router1', 'os' => null]);
    createFilterableDevice(['hostname' => 'router2', 'os' => '']);
    createFilterableDevice(['hostname' => 'router3', 'os' => 'ios']);

    $results = FilterableDevice::applyFilters(['os' => ['is_not_empty' => null]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router3');
});

// -------------------------------------------------------------------------
// Set operators
// -------------------------------------------------------------------------

test('in', function (): void {
    createFilterableDevice(['hostname' => 'router1', 'os' => 'ios']);
    createFilterableDevice(['hostname' => 'router2', 'os' => 'eos']);
    createFilterableDevice(['hostname' => 'router3', 'os' => 'junos']);

    $results = FilterableDevice::applyFilters(['os' => ['in' => ['ios', 'eos']]])->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('os')->all())->toEqualCanonicalizing(['ios', 'eos']);
});

test('not in', function (): void {
    createFilterableDevice(['hostname' => 'router1', 'os' => 'ios']);
    createFilterableDevice(['hostname' => 'router2', 'os' => 'eos']);
    createFilterableDevice(['hostname' => 'router3', 'os' => 'junos']);

    $results = FilterableDevice::applyFilters(['os' => ['not_in' => ['ios', 'eos']]])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->os)->toEqual('junos');
});

test('in with comma separated string', function (): void {
    createFilterableDevice(['hostname' => 'router1', 'os' => 'ios']);
    createFilterableDevice(['hostname' => 'router2', 'os' => 'eos']);
    createFilterableDevice(['hostname' => 'router3', 'os' => 'junos']);

    $results = FilterableDevice::applyFilters(['os' => ['in' => 'ios,eos']])->get();

    expect($results)->toHaveCount(2);
});

test('in operator with empty values', function (): void {
    createFilterableDevice(['hostname' => 'router1', 'os' => 'ios']);

    $results = FilterableDevice::applyFilters(['os' => ['in' => []]])->get();
    expect($results)->toHaveCount(0);

    $results = FilterableDevice::applyFilters(['os' => ['in' => '']])->get();
    expect($results)->toHaveCount(0);
});

test('multiple fields apply and logic', function (): void {
    createFilterableDevice(['hostname' => 'core-rt-01', 'os' => 'ios']);
    createFilterableDevice(['hostname' => 'edge-rt-01', 'os' => 'ios']);
    createFilterableDevice(['hostname' => 'core-rt-01', 'os' => 'junos']);

    $results = FilterableDevice::applyFilters([
        'hostname' => ['contains' => 'core'],
        'os' => ['eq' => 'ios'],
    ])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-rt-01');
    expect($results->first()->os)->toEqual('ios');
});

// -------------------------------------------------------------------------
// Relation filtering
// -------------------------------------------------------------------------

test('relation eq', function (): void {
    createFilterableDevice(['hostname' => 'router1'], 'London');
    createFilterableDevice(['hostname' => 'router2'], 'Paris');

    $results = FilterableDevice::applyFilters(['location.location' => ['eq' => 'London']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('relation contains', function (): void {
    createFilterableDevice(['hostname' => 'router1'], 'London DC');
    createFilterableDevice(['hostname' => 'router2'], 'Paris DC');

    $results = FilterableDevice::applyFilters(['location.location' => ['contains' => 'London']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router1');
});

test('relation neq', function (): void {
    createFilterableDevice(['hostname' => 'router1'], 'London');
    createFilterableDevice(['hostname' => 'router2'], 'Paris');

    $results = FilterableDevice::applyFilters(['location.location' => ['neq' => 'London']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('router2');
});

test('deeply nested relation filtering', function (): void {
    createFilterableDevice(['hostname' => 'uk-router'], 'London', 'Europe');
    createFilterableDevice(['hostname' => 'us-router']);

    $results = FilterableDevice::applyFilters([
        'location.region.name' => ['eq' => 'Europe'],
    ])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('uk-router');
});

test('relation neq includes records with no relation', function (): void {
    createFilterableDevice(['hostname' => 'lon-gw'], 'London');
    createFilterableDevice(['hostname' => 'par-gw'], 'Paris');
    createFilterableDevice(['hostname' => 'orphan-gw', 'location_id' => null]);

    $results = FilterableDevice::applyFilters([
        'location.location' => ['neq' => 'London'],
    ])->get();

    expect($results)->toHaveCount(2);
    $hostnames = $results->pluck('hostname')->all();
    expect($hostnames)->toContain('par-gw');
    expect($hostnames)->toContain('orphan-gw');
});

test('relation is empty finds records without relationship', function (): void {
    createFilterableDevice(['hostname' => 'connected-gw'], 'London');
    createFilterableDevice(['hostname' => 'unassigned-gw', 'location_id' => null]);

    $results = FilterableDevice::applyFilters([
        'location.location' => ['is_empty' => '1'],
    ])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('unassigned-gw');
});

test('relation is not empty finds records with relationship', function (): void {
    createFilterableDevice(['hostname' => 'connected-gw'], 'Paris');
    createFilterableDevice(['hostname' => 'unassigned-gw', 'location_id' => null]);

    $results = FilterableDevice::applyFilters([
        'location.location' => ['is_not_empty' => '1'],
    ])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('connected-gw');
});

// -------------------------------------------------------------------------
// applyFilterSearch helper
// -------------------------------------------------------------------------

test('apply filter search or logic', function (): void {
    createFilterableDevice(['hostname' => 'core-router', 'os' => 'ios']);
    createFilterableDevice(['hostname' => 'edge-switch', 'os' => 'eos']);
    createFilterableDevice(['hostname' => 'spine-leaf',  'os' => 'junos']);

    // filterSearch on Device searches both hostname and os
    $results = FilterableDevice::applyFilters(['search' => ['contains' => 'ios']])->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('core-router');
});

test('apply filter search not contains and logic', function (): void {
    createFilterableDevice(['hostname' => 'core-router', 'os' => 'ios']);
    createFilterableDevice(['hostname' => 'edge-switch', 'os' => 'eos']);

    $results = FilterableDevice::applyFilters(['search' => ['not_contains' => 'ios']])->get();

    // AND logic: hostname NOT LIKE AND os NOT LIKE — only edge-switch qualifies
    expect($results)->toHaveCount(1);
    expect($results->first()->hostname)->toEqual('edge-switch');
});

// -------------------------------------------------------------------------
// Ignored / invalid input
// -------------------------------------------------------------------------

test('non filterable field is ignored', function (): void {
    createFilterableDevice(['hostname' => 'router1']);

    $results = FilterableDevice::applyFilters(['secret' => ['eq' => 'value']])->get();

    expect($results)->toHaveCount(1);
});

test('unknown operator is ignored', function (): void {
    createFilterableDevice(['hostname' => 'router1']);

    $results = FilterableDevice::applyFilters(['hostname' => ['bogus' => 'value']])->get();

    expect($results)->toHaveCount(1);
});

test('non array operators are ignored', function (): void {
    createFilterableDevice(['hostname' => 'router1']);

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
    protected $connection = 'testing_memory';
    protected $table = 'filterable_regions';
    protected $guarded = [];
    public $timestamps = false;
}

/**
 * @property int $id
 * @property string|null $location
 * @property int|null $region_id
 */
class FilterableLocation extends Model
{
    protected $connection = 'testing_memory';
    protected $table = 'filterable_locations';
    protected $guarded = [];
    public $timestamps = false;

    /**
     * @return BelongsTo<FilterableRegion, $this>
     */
    public function region(): BelongsTo
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
    use Filterable;

    protected $connection = 'testing_memory';
    protected $table = 'filterable_devices';
    protected $guarded = [];
    public $timestamps = false;

    /** @var list<string> */
    protected array $filterable = ['hostname', 'os', 'uptime', 'location.location', 'search', 'location.region.name'];

    /**
     * @return BelongsTo<FilterableLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(FilterableLocation::class, 'location_id');
    }

    /**
     * @param  Builder<static>  $query
     * @param  scalar|scalar[]  $value
     * @param  array{operator?: string, wildcard?: string, not?: bool, null?: bool, set?: bool}  $config
     * @return void
     */
    public function filterSearch(Builder $query, mixed $value, array $config): void
    {
        $this->applyFilterSearch(['hostname', 'os'], $query, $value, $config);
    }
}
