<?php

use App\Data\Graphing\GraphParameters;
use App\Graphs\GraphAccess;
use App\Graphs\GraphFactory;
use App\Graphs\Vminfo\CpuGraph;
use App\Graphs\Vminfo\DiskioBitsGraph;
use App\Graphs\Vminfo\MemGraph;
use App\Graphs\Vminfo\NetGraph;
use App\Models\Device;
use App\Models\User;
use App\Models\Vminfo;
use Illuminate\Support\Facades\Gate;
use LibreNMS\Exceptions\NoGraphData;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('graph types resolve to classes', function (): void {
    $factory = app(GraphFactory::class);

    expect($factory->graphFor(new GraphParameters(['type' => 'vminfo_cpu', 'id' => 1])))->toBeInstanceOf(CpuGraph::class);
    expect($factory->graphFor(new GraphParameters(['type' => 'vminfo_diskio_bits', 'id' => 1])))->toBeInstanceOf(DiskioBitsGraph::class);
    expect($factory->subtypes('vminfo'))->toEqualCanonicalizing(['cpu', 'mem', 'disk', 'diskio_bits', 'diskio_ops', 'net']);
});

test('series use the metrics of the vm', function (): void {
    $device = Device::factory()->create(['hostname' => 'pve.example.com']);
    $vm = Vminfo::factory()->for($device)->create(['vm_type' => 'proxmox', 'vmwVmVMID' => 100]);

    $mem = new MemGraph(new GraphParameters(['type' => 'vminfo_mem', 'id' => $vm->id]))->getSeries();
    expect(array_keys($mem))->toBe(['used', 'total']);
    expect($mem['used']->metric)->toEqual($vm->metricIdentity('vm.memory'));

    $net = new NetGraph(new GraphParameters(['type' => 'vminfo_net', 'id' => $vm->id]))->getSeries();
    expect($net['in']->scale)->toBe(8.0);
    expect($net['in']->style->invert)->toBeFalse();
    expect($net['out']->style->invert)->toBeTrue();
});

test('title and page context', function (): void {
    $device = Device::factory()->create();
    $vm = Vminfo::factory()->for($device)->create(['vmwVmDisplayName' => 'web']);

    $graph = new CpuGraph(new GraphParameters(['type' => 'vminfo_cpu', 'id' => $vm->id]));

    expect($graph->getTitle())->toBe($device->shortDisplayName() . '::web');
    expect($graph->getPageContext()->device?->device_id)->toBe($device->device_id);
    expect($graph->getPageContext()->subtitle)->toBe(' :: VM :: web');
});

test('missing vm has no data', function (): void {
    $graph = new CpuGraph(new GraphParameters(['type' => 'vminfo_cpu', 'id' => 999999]));

    expect(app(GraphAccess::class)->trusted(fn () => $graph->authorize()))->toBeFalse();

    $this->expectException(NoGraphData::class);
    $graph->getSeries();
});

test('authorizes based on the vm', function (): void {
    $vm = Vminfo::factory()->create();
    $graph = new CpuGraph(new GraphParameters(['type' => 'vminfo_cpu', 'id' => $vm->id]));

    $this->actingAs(User::factory()->create());

    Gate::shouldReceive('allows')
        ->with('view', Mockery::on(fn ($model) => $model instanceof Vminfo && $model->id === $vm->id))
        ->once()
        ->andReturn(false);

    expect($graph->authorize())->toBeFalse();
});