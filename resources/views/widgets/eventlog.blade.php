{{-- the dashboard sends a jquery refresh event to the first element instead of reloading the html (data-reload=false) --}}
<x-eventlog-table :device-id="$device ? (int) $device : null"
                  :filter="$eventtype ? ['type' => ['eq' => $eventtype]] : []"
                  :filterable="false"
                  :params="array_filter([
                      'device_group' => $device_group,
                      'age' => $age ? \LibreNMS\Util\Time::durationToSeconds($age) : null,
                      'search' => $message_filter,
                  ])"
                  :panel="false"
                  :footer="! $hidenavigation"
                  :title="null"
                  data-reload="false"
                  x-init="$($el).on('refresh', () => reload())" />
