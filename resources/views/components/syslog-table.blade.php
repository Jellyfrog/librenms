@props([
    'deviceId' => null,
    'filter' => [],
    'filterable' => true,
    'params' => [],
    'panel' => true,
    'footer' => true,
    'title' => __('Syslog'),
])

{{--
    Syslog table. deviceId limits it to one device.
    filter: initial x-filter filters, sent even when filterable is false (no filter controls, for widgets)
    params: extra endpoint params: device_group, level (0-7, this level and more severe), search (message)
--}}
<x-data-table :name="$deviceId ? 'syslog-device' : 'syslog'"
              :url="route('table.syslog')"
              :params="array_filter(['device' => $deviceId, ...$params], fn ($value) => $value !== null && $value !== '')"
              :sort="['timestamp' => 'desc']"
              :search="false"
              :filter-name="$filterable ? 'syslog' : null"
              :filter-fields="$filterable ? \App\Models\Syslog::filterFieldDefinitions($deviceId) : []"
              :filter="$filter"
              :column-toggle="$filterable"
              :panel="$panel"
              :footer="$footer"
              :title="$title"
              {{ $attributes }}>
    <x-data-table.column field="label" :sortable="false" :hideable="false" width="1px">
        <span class="alert-status" :class="row.label"></span>
    </x-data-table.column>
    <x-data-table.column field="timestamp" :label="__('Timestamp')" class="tw:whitespace-nowrap" />
    <x-data-table.column field="level" :label="__('Level')" />
    <x-data-table.column field="device_id" :label="__('device.attributes.hostname')" :visible="$deviceId === null" html />
    <x-data-table.column field="program" :label="__('Program')" />
    <x-data-table.column field="msg" :label="__('Message')" />
    <x-data-table.column field="priority" :label="__('Priority')" />
</x-data-table>
