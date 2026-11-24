{{-- the dashboard sends a jquery refresh event to the first element instead of reloading the html (data-reload=false) --}}
<x-syslog-table :device-id="$device ? (int) $device : null"
                :filterable="false"
                :params="['device_group' => $device_group, 'level' => $level]"
                :panel="false"
                :footer="! $hidenavigation"
                :title="null"
                data-reload="false"
                x-init="$($el).on('refresh', () => reload())" />
