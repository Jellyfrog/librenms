{{-- the dashboard sends a jquery refresh event to the first element instead of reloading the html (data-reload=false) --}}
<x-alert-log-table :name="'alert-log-widget-' . $id"
                   :params="[
                       'device_id' => $device_id,
                       'device_group' => $device_group,
                       'state' => $state,
                       'severity' => array_values(array_filter((array) $severity)),
                       'from' => $from,
                       'to' => $to,
                   ]"
                   :severity="false"
                   :date-format="['dateStyle' => 'medium', 'timeStyle' => 'short']"
                   :panel="false"
                   :footer="! $hidenavigation"
                   data-reload="false"
                   x-init="$($el).on('refresh', () => reload())" />
