{{-- the dashboard sends a jquery refresh event to the first element instead of reloading the html (data-reload=false) --}}
<x-data-table :name="'alertlog-stats-' . $id"
              :url="route('table.alertlog-stats')"
              :params="array_filter(['min_severity' => $min_severity, 'time_interval' => $time_interval])"
              :sort="['count' => 'desc']"
              :column-toggle="false"
              :panel="false"
              :footer="! $hidenavigation"
              data-reload="false"
              x-init="$($el).on('refresh', () => reload())">
    <x-data-table.column field="count" :label="__('Count')" width="1px" />
    <x-data-table.column field="hostname" :label="__('Device')" html />
    <x-data-table.column field="alert_rule" :label="__('Alert rule')" />
</x-data-table>
