@props([
    'deviceId' => null,
    'filter' => [],
    'filterable' => true,
    'perPage' => 25,
    'panel' => true,
    'footer' => true,
    'title' => __('Graylog'),
])

{{--
    Graylog messages, searched through the Graylog API. deviceId limits it to one device.
    filter: initial filters: stream, device_id, loglevel and range (seconds), sent even when filterable is false (widgets)
--}}
@php
    $levels = collect(range(0, 7))->mapWithKeys(fn ($level) => [$level => "($level) " . __("syslog.severity.$level")])->all();
    $ranges = [
        0 => __('All time'),
        300 => __('Last 5 minutes'),
        900 => __('Last 15 minutes'),
        1800 => __('Last 30 minutes'),
        3600 => __('Last hour'),
        7200 => __('Last 2 hours'),
        28800 => __('Last 8 hours'),
        86400 => __('Last day'),
        172800 => __('Last 2 days'),
        432000 => __('Last 5 days'),
        604800 => __('Last 7 days'),
        1209600 => __('Last 14 days'),
        2592000 => __('Last 30 days'),
    ];
    $filterFields = array_values(array_filter([
        ['key' => 'stream', 'label' => __('Stream'), 'type' => 'select', 'endpoint' => route('ajax.select.graylog-streams')],
        $deviceId === null ? ['key' => 'device_id', 'label' => __('Device'), 'type' => 'select', 'endpoint' => route('ajax.select.device')] : null,
        ['key' => 'loglevel', 'label' => __('Log Level'), 'type' => 'select', 'options' => $levels],
        ['key' => 'range', 'label' => __('Time range'), 'type' => 'select', 'options' => $ranges],
    ]));
@endphp
<x-data-table :name="$deviceId ? 'graylog-device' : 'graylog'"
              :url="route('table.graylog')"
              :params="array_filter(['device' => $deviceId])"
              :sort="['timestamp' => 'desc']"
              :per-page="$perPage"
              :per-page-options="array_values(array_unique([$perPage, 25, 50, 100, 250]))"
              :filter-name="$filterable ? 'graylog' : null"
              :filter-fields="$filterable ? $filterFields : []"
              :filter="$filter"
              :column-toggle="$filterable"
              :search="$filterable"
              :panel="$panel"
              :footer="$footer"
              :title="$title"
              :config="['localTime' => ! \App\Facades\LibrenmsConfig::get('graylog.timezone')]"
              {{ $attributes }}>
    <x-data-table.column field="severity" :sortable="false" :hideable="false" width="1px">
        <span class="alert-status" :class="row.severity"></span>
    </x-data-table.column>
    <x-data-table.column field="origin" :label="__('Origin')" html />
    {{-- without a configured timezone Graylog times are shown in the browser's time zone --}}
    <x-data-table.column field="timestamp" :label="__('Timestamp')" class="tw:whitespace-nowrap"
                         x-text="config.localTime ? moment.parseZone(row.timestamp).local().format('YYYY-MM-DD HH:mm:ss') : row.timestamp" />
    <x-data-table.column field="level" :label="__('Level')" />
    <x-data-table.column field="source" :label="__('Source')" :visible="$deviceId === null" html />
    <x-data-table.column field="message" :label="__('Message')" :sortable="false" />
    <x-data-table.column field="facility" :label="__('Facility')" />
</x-data-table>
