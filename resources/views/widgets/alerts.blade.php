{{-- the dashboard sends a jquery refresh event to the first element instead of reloading the html (data-reload=false) --}}
<x-alerts-table :name="'alerts-widget-' . $id"
                :params="[
                    'acknowledged' => $acknowledged,
                    'unreachable' => $unreachable,
                    'fired' => $fired,
                    'state' => $state,
                    'min_severity' => $min_severity,
                    'group' => $device_group,
                    'uncollapse_key_count' => $uncollapse_key_count,
                    'device_id' => $device,
                ]"
                :sort="$sort === 'severity' || $sort == 1 ? ['severity' => 'desc'] : ['timestamp' => 'desc']"
                :location="(bool) $location"
                :proc="(bool) $proc"
                :panel="false"
                :footer="! $hidenavigation"
                data-reload="false"
                x-init="$($el).on('refresh', () => reload())"
                x-effect="let counter = document.getElementById('widget_title_counter_{{ $id }}'); if (counter) counter.textContent = total ? ' (' + total + ')' : ''">
    <x-slot:heading>
        {{-- quick filters: only warning or only critical alerts, click again for the widget setting --}}
        <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Alert severity') }}">
            @foreach (['warning' => [5, 'btn-warning', __('Warning')], 'critical' => [6, 'btn-danger', __('Critical')]] as [$severity, $activeClass, $label])
                <button type="button" class="btn"
                        :class="config.params.min_severity == {{ $severity }} ? '{{ $activeClass }}' : 'btn-default'"
                        :aria-pressed="config.params.min_severity == {{ $severity }}"
                        x-on:click="config.params.min_severity = config.params.min_severity == {{ $severity }} ? @js($min_severity) : {{ $severity }}; changed(true)">{{ $label }}</button>
            @endforeach
        </div>
    </x-slot:heading>
</x-alerts-table>
