<?php

use App\Data\TimeSeries\MetricCollector;
use App\Models\Vminfo;
use Illuminate\Support\Collection;
use LibreNMS\OS\VmwareEsxi;

uses(\LibreNMS\Tests\TestCase::class);

test('a failed walk keeps the vms', function (): void {
    $vms = new Collection([new Vminfo(['vm_type' => 'vmware', 'vmwVmVMID' => 1])]);

    expect(esxiOs(new Collection)->pollVminfo($vms, new MetricCollector))->toBe($vms);
});

test('poll returns the walked vms', function (): void {
    $walked = new Collection([new Vminfo(['vm_type' => 'vmware', 'vmwVmVMID' => 2])]);
    $vms = new Collection([new Vminfo(['vm_type' => 'vmware', 'vmwVmVMID' => 1])]);

    expect(esxiOs($walked)->pollVminfo($vms, new MetricCollector))->toBe($walked);
});

/**
 * @param  Collection<int, Vminfo>  $walked
 */
function esxiOs(Collection $walked): VmwareEsxi
{
    $device = ['device_id' => 1, 'os' => 'vmware-esxi'];

    return new class($device, $walked) extends VmwareEsxi
    {
        /**
         * @param  array<mixed>  $device
         * @param  Collection<int, Vminfo>  $walked
         */
        public function __construct(array &$device, private readonly Collection $walked)
        {
            parent::__construct($device);
        }

        /**
         * @return Collection<int, Vminfo>
         */
        public function discoverVmInfo(): Collection
        {
            return $this->walked;
        }
    };
}
