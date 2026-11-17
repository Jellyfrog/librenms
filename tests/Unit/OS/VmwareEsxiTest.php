<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\TimeSeries\MetricCollector;
use \LibreNMS\OS\VmwareEsxi;
use App\Models\Vminfo;
use Illuminate\Support\Collection;

test('afailed walk keeps the vms', function () {
    $vms = new Collection([new Vminfo(['vm_type' => 'vmware', 'vmwVmVMID' => 1])]);

    expect(os(new Collection)->pollVminfo($vms, new MetricCollector))->toBe($vms);
});

test('poll returns the walked vms', function () {
    $walked = new Collection([new Vminfo(['vm_type' => 'vmware', 'vmwVmVMID' => 2])]);
    $vms = new Collection([new Vminfo(['vm_type' => 'vmware', 'vmwVmVMID' => 1])]);

    expect(os($walked)->pollVminfo($vms, new MetricCollector))->toBe($walked);
});

/**
 * @param  Collection<int, Vminfo>  $walked
 */
function os(Collection $walked): VmwareEsxi
{
    $device = ['device_id' => 1, 'os' => 'vmware-esxi'];

    return new class($device, $walked) extends VmwareEsxi
    {
        /**
         * @param  array<mixed>  $device
         * @param  Collection<int, Vminfo>  $walked
         */
        function __construct(array &$device, private readonly Collection $walked)
        {
            __construct($device);
        }

        /**
         * @return Collection<int, Vminfo>
         */
        function discoverVmInfo(): Collection
        {
            return $this->walked;
        }
    };
}
