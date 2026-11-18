@props([
    'deviceId' => null,
    'filter' => [],
    'panel' => true,
    'title' => __('Eventlog'),
])

{{-- Eventlog table with x-filter filters. deviceId limits it to one device --}}
<x-data-table :name="$deviceId ? 'eventlog-device' : 'eventlog'"
              :url="route('table.eventlog')"
              :params="array_filter(['device' => $deviceId])"
              :sort="['datetime' => 'desc']"
              :per-page-options="[50, 100, 250, -1]"
              :search="false"
              filter-name="eventlog"
              :filter-fields="\App\Models\Eventlog::filterFieldDefinitions($deviceId)"
              :filter="$filter"
              :panel="$panel"
              :title="$title"
              {{ $attributes }}>
    <x-data-table.column field="datetime" :label="__('Timestamp')" x-html="row.datetime" />
    <x-data-table.column field="type" :label="__('Type')" x-html="row.type" />
    <x-data-table.column field="device_id" :label="__('device.attributes.hostname')" :visible="$deviceId === null" x-html="row.device_id" />
    <x-data-table.column field="message" :label="__('Message')" x-html="row.message" />
    <x-data-table.column field="username" :label="__('User')" />
</x-data-table>
