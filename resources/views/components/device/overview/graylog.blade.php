@props(['device', 'config'])

@if($config)
    <div x-data="graylogOverview(@js([
            'url' => $config['url'],
            'device' => $device->device_id,
            'rowCount' => $config['rowCount'],
            'loglevel' => $config['loglevel'],
        ]))" x-init="load" x-show="loading || rows.length">
        <x-device.overview.panel :title="__('Recent Graylog')" icon="fa fa-clone" :href="route('device.graylog', ['device' => $device->device_id])">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead><tr><th></th><th>{{ __('Timestamp') }}</th><th>{{ __('Level') }}</th><th>{{ __('Message') }}</th><th>{{ __('Facility') }}</th></tr></thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="index">
                            <tr>
                                <td><span class="alert-status" :class="row.severity"></span></td>
                                <td x-text="row.timestamp"></td>
                                <td x-text="row.level"></td>
                                <td x-text="row.message"></td>
                                <td x-text="row.facility"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="loading" class="tw:p-3 tw:text-center tw:text-gray-500">{{ __('Loading') }}…</div>
            </div>
        </x-device.overview.panel>
    </div>

    @pushOnce('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('graylogOverview', (config) => ({
                    loading: true,
                    rows: [],

                    load() {
                        window.axios.get(config.url, {params: {
                            device: config.device,
                            per_page: config.rowCount,
                            filter: config.loglevel === null ? {} : {loglevel: {eq: config.loglevel}},
                        }})
                            .then((response) => this.rows = response.data.data ?? [])
                            .catch(() => {})
                            .finally(() => this.loading = false);
                    },
                }));
            });
        </script>
    @endPushOnce
@endif
