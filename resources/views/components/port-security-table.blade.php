@props([
    'deviceId' => null,
    'filterName' => 'port-security',
    'filterFields' => [],
    'filter' => [],
])

{{-- Port security of every port, or of one device --}}
<x-data-table :name="$deviceId ? 'port-security-device' : 'port-security'"
              :url="route('table.port-security')"
              :export="route('port-security.export')"
              :params="array_filter(['device_id' => $deviceId])"
              :sort="$deviceId ? ['port' => 'asc'] : ['device' => 'asc', 'port' => 'asc']"
              :search="false"
              :filter-name="$filterName"
              :filter-fields="$filterFields"
              :filter="$filter"
              {{ $attributes }}>
    @unless($deviceId)
        <x-data-table.column field="device" :label="__('Device')" html />
    @endunless
    <x-data-table.column field="port" :label="__('Port')" :hideable="false" html />
    <x-data-table.column field="ifAlias" :label="__('Description')" />
    <x-data-table.column field="enabled" :label="__('Enabled')" :sortable="false" />
    <x-data-table.column field="status" :label="__('Status')">
        <i class="fa" :class="row.status_icon" aria-hidden="true" :title="row.status"></i> <span x-text="row.status"></span>
    </x-data-table.column>
    <x-data-table.column field="address_count" :label="__('Current MACs')" />
    <x-data-table.column field="max_addresses" :label="__('Max MACs')" />
    <x-data-table.column field="violation_action" :label="__('Violation Action')" />
    <x-data-table.column field="violation_count" :label="__('Violations')" />
    <x-data-table.column field="last_mac_address" :label="__('Last MAC')" />
    <x-data-table.column field="sticky" :label="__('Sticky')" :sortable="false" />
    <x-slot:empty>{{ __('No port security entries matched the current filters.') }}</x-slot:empty>
</x-data-table>
