@props([
    'name' => 'alerts',
    'filterName' => null,
    'filterFields' => [],
    'filter' => [],
    'params' => [],
    'sort' => ['timestamp' => 'desc'],
    'location' => true,
    'proc' => false,
    'panel' => true,
    'footer' => true,
    'title' => null,
])

{{--
    Open alerts. params: endpoint params of App\Http\Controllers\Table\AlertsController (device_id, alert_id, min_severity...)
    The ack, notes and details modals must be on the page: alerts.modals.*
    heading slot: extra toolbar controls, they can use the table state
--}}
<x-data-table :name="$name"
              :url="route('table.alerts')"
              :params="array_filter($params, fn ($value) => $value !== null && $value !== '')"
              :sort="$sort"
              :search="false"
              :filter-name="$filterName"
              :filter-fields="$filterFields"
              :filter="$filter"
              :column-toggle="(bool) $filterName"
              :panel="$panel"
              :footer="$footer"
              :title="$title"
              {{ $attributes->class(['alert-table']) }}>
    @isset($heading)
        <x-slot:heading>{{ $heading }}</x-slot:heading>
    @endisset
    <x-data-table.column field="severity" :hideable="false" width="1px" html />
    <x-data-table.column field="timestamp" :label="__('Timestamp')" class="tw:whitespace-nowrap" />
    <x-data-table.column field="rule" :label="__('Rule')" html />
    <x-data-table.column field="details" :sortable="false" :hideable="false" width="1px" html />
    <x-data-table.column field="hostname" :label="__('Hostname')" html />
    <x-data-table.column field="location" :label="__('Location')" :visible="$location" html />
    <x-data-table.column field="ack_ico" :label="__('ACK')" :sortable="false" html />
    <x-data-table.column field="notes" :label="__('Notes')" :sortable="false" html />
    <x-data-table.column field="proc" label="URL" :sortable="false" :visible="$proc" html />
    @can('alert.detail')
        <x-data-table.column field="verbose_details" :label="__('Details')" :sortable="false" html />
    @endcan
</x-data-table>
@include('alerts.partials.table-scripts')
