{{-- the dashboard sends a jquery refresh event to the first element instead of reloading the html (data-reload=false) --}}
<x-graylog-table :device-id="$device ? (int) $device : null"
                 :filter="array_map(fn ($value) => ['eq' => $value], array_filter(['stream' => $stream, 'range' => $range, 'loglevel' => $loglevel], fn ($value) => $value !== null && $value !== ''))"
                 :filterable="false"
                 :per-page="(int) $limit ?: 15"
                 :panel="false"
                 :footer="! $hidenavigation"
                 :title="null"
                 data-reload="false"
                 x-init="$($el).on('refresh', () => reload())" />
