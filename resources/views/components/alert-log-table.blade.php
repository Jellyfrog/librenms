@props([
    'name' => 'alert-log',
    'filterName' => null,
    'filterFields' => [],
    'filter' => [],
    'params' => [],
    'severity' => true,
    'panel' => true,
    'footer' => true,
    'title' => null,
    'dateFormat' => [],
])

{{--
    Alert log. params: endpoint params of App\Http\Controllers\Table\AlertLogController (device_id, device_group, state, severity, from, to)
    Clicking a row shows its incident details. The details modal must be on the page: alerts.modals.details
--}}
<x-data-table :name="$name"
              :url="route('table.alertlog')"
              :params="array_filter($params, fn ($value) => $value !== null && $value !== '' && $value !== [])"
              :sort="['time_logged' => 'desc']"
              :search="false"
              :filter-name="$filterName"
              :filter-fields="$filterFields"
              :filter="$filter"
              :column-toggle="(bool) $filterName"
              :panel="$panel"
              :footer="$footer"
              :title="$title"
              :config="$dateFormat ? ['dateFormat' => $dateFormat] : []"
              data-expand-on-row-click
              {{ $attributes->class(['alert-table']) }}>
    <x-data-table.column field="status" :label="__('State')" :hideable="false" width="1px" html />
    <x-data-table.column field="time_logged" :label="__('Timestamp')" class="tw:whitespace-nowrap" x-text="LibreNMS.Date.display(row.time_logged, config.dateFormat)" />
    <x-data-table.column field="details" :sortable="false" :hideable="false" width="1px" html />
    <x-data-table.column field="hostname" :label="__('Device')" html />
    <x-data-table.column field="alert_rule" :label="__('Alert')" html />
    <x-data-table.column field="severity" :label="__('Severity')" :visible="$severity" />
    @can('alert.detail')
        <x-data-table.column field="verbose_details" :label="__('Details')" :sortable="false" html />
    @endcan
</x-data-table>
@include('alerts.partials.table-scripts')
