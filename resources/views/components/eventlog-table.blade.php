@props([
    'deviceId' => null,
    'filter' => [],
    'filterable' => true,
    'params' => [],
    'panel' => true,
    'footer' => true,
    'title' => __('Eventlog'),
])

{{--
    Eventlog table. deviceId limits it to one device.
    filter: initial x-filter filters, sent even when filterable is false (no filter controls, for widgets)
    params: extra endpoint params: device_group, age (seconds), search (message)
--}}
<x-data-table :name="$deviceId ? 'eventlog-device' : 'eventlog'"
              :url="route('table.eventlog')"
              :params="array_filter(['device' => $deviceId, ...$params])"
              :sort="['datetime' => 'desc']"
              :search="false"
              :filter-name="$filterable ? 'eventlog' : null"
              :filter-fields="$filterable ? \App\Models\Eventlog::filterFieldDefinitions($deviceId) : []"
              :filter="$filter"
              :column-toggle="$filterable"
              :panel="$panel"
              :footer="$footer"
              :title="$title"
              {{ $attributes }}>
    <x-data-table.column field="datetime" :label="__('Timestamp')" html />
    <x-data-table.column field="type" :label="__('Type')" html />
    <x-data-table.column field="device_id" :label="__('device.attributes.hostname')" :visible="$deviceId === null" html />
    <x-data-table.column field="message" :label="__('Message')" />
    <x-data-table.column field="username" :label="__('User')" />
</x-data-table>
