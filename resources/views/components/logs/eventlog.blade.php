@props([
    'device' => null,
    'filterName',
    'filter' => [],
    'filterFields' => [],
])

{{-- Eventlog table with x-filter filters, device limits it to one device --}}
<x-data-table :name="$device ? 'eventlog-device' : 'eventlog'"
              :url="route('table.eventlog')"
              :params="array_filter(['device' => $device?->device_id])"
              :sort="['datetime' => 'desc']"
              :search="false"
              :filter-name="$filterName"
              :filter-fields="$filterFields"
              :filter="$filter"
              :title="__('Eventlog')"
              {{ $attributes }}>
    <x-data-table.column field="datetime" :label="__('Timestamp')" html />
    <x-data-table.column field="type" :label="__('Type')" html />
    <x-data-table.column field="device_id" :label="__('device.attributes.hostname')" :visible="$device === null" html />
    <x-data-table.column field="message" :label="__('Message')" html />
    <x-data-table.column field="username" :label="__('User')" />
</x-data-table>
