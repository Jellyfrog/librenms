@props([
    'name' => 'outages',
    'params' => [],
    'title' => __('Outages'),
])

{{-- Device outages. params: device, status (current, previous, all), from, to --}}
<x-data-table :name="$name"
              :url="route('table.outages')"
              :params="array_filter($params, fn ($value) => $value !== null && $value !== '')"
              :sort="['going_down' => 'desc']"
              :search="false"
              :title="$title"
              {{ $attributes }}>
    @isset($heading)
        <x-slot:heading>{{ $heading }}</x-slot:heading>
    @endisset
    <x-data-table.column field="status" :sortable="false" :hideable="false" width="1px">
        <span class="alert-status" :class="row.status"></span>
    </x-data-table.column>
    <x-data-table.column field="going_down" :label="__('Start')" class="tw:whitespace-nowrap" />
    <x-data-table.column field="up_again" :label="__('End')" class="tw:whitespace-nowrap" :visible="($params['status'] ?? 'all') !== 'current'" />
    <x-data-table.column field="device_id" :label="__('device.attributes.hostname')" html />
    <x-data-table.column field="duration" :label="__('Duration')" :sortable="false" />
</x-data-table>
