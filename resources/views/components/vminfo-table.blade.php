@props([
    'deviceId' => null,
    'filterFields' => [],
    'filter' => [],
])

{{-- Virtual machines, deviceId limits it to the VMs of one host --}}
<x-data-table :name="$deviceId ? 'vminfo-device' : 'vminfo'"
              :url="route('table.vminfo')"
              export
              :params="array_filter(['device_id' => $deviceId])"
              :sort="['vmwVmDisplayName' => 'asc']"
              :search="false"
              filter-name="vminfo"
              :filter-fields="$filterFields"
              :filter="$filter"
              {{ $attributes }}>
    <x-data-table.column field="deviceid" :label="__('Device Id')" :sortable="false" :visible="false" />
    <x-data-table.column field="sysname" :label="__('Sysname')" :sortable="false" :visible="false" />
    <x-data-table.column field="vmwVmDisplayName" :label="__('VM Name')" :hideable="false" html />
    @unless($deviceId)
        <x-data-table.column field="hostname" :label="__('Host')" html />
    @endunless
    <x-data-table.column field="vmwVmState" :label="__('Power Status')" html />
    <x-data-table.column field="vm_type" :label="__('Type')" />
    <x-data-table.column field="vmwVmGuestOS" :label="__('Operating System')" />
    <x-data-table.column field="vmwVmMemSize" :label="__('Memory')" />
    <x-data-table.column field="vmwVmCpus" :label="__('vCPUs')" />
    <x-data-table.column field="graphs" :label="__('Graphs')" :sortable="false">
        <a :href="row.graphs_url" title="{{ __('Graphs') }}"><i class="fa fa-area-chart fa-lg" aria-hidden="true"></i></a>
    </x-data-table.column>
</x-data-table>
