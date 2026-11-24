@extends('layouts.librenmsv1')

@section('title', __('port.groups.manage') . ' - ' . $port_group->name)

@section('content')
    <div class="container-fluid">
        <div class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-3 tw:mb-4">
            <div>
                <h3 class="tw:m-0 tw:text-xl tw:font-semibold">
                    <a href="{{ route('port-groups.index') }}">{{ __('Port Groups') }}</a>
                    <i class="fa fa-angle-right tw:mx-1 tw:text-gray-400" aria-hidden="true"></i>
                    {{ $port_group->name }}
                </h3>
                @if ($port_group->desc)
                    <div class="tw:mt-1 tw:text-gray-500 tw:dark:text-dark-white-400">{{ $port_group->desc }}</div>
                @endif
            </div>
            <div class="tw:flex tw:flex-wrap tw:gap-2">
                <a href="{{ route('port-groups.index') }}" class="lnms-btn lnms-btn-primary">
                    <i class="fa fa-check" aria-hidden="true"></i> {{ __('Done') }}
                </a>
            </div>
        </div>

        <x-data-table component="portGroupPorts"
                      name="port-group-ports"
                      :url="route('port-groups.ports.list', $port_group)"
                      row-key="port_id"
                      row-label="row.device + ' ' + row.label"
                      :sort="['device' => 'asc']"
                      :per-page-options="[25, 50, 100, 250]"
                      :search="false"
                      filter-name="port-group.ports"
                      :filter-fields="$filter_fields"
                      :filter="$filter"
                      :page-selected="__('port.settings.page_selected', ['count' => '__count__'])"
                      :select-all-matching="__('port.settings.select_all_matching', ['count' => '__count__'])"
                      :all-matching-selected="__('port.settings.all_matching_selected', ['count' => '__count__'])"
                      :config="[
                          'membership' => $membership,
                          'members' => $members,
                          'updateUrl' => route('port-groups.ports.update', $port_group),
                      ]"
                      :lang="[
                          'confirm_add' => __('port.groups.confirm_add', ['count' => '__count__']),
                          'confirm_remove' => __('port.groups.confirm_remove', ['count' => '__count__']),
                          'add' => __('port.groups.add'),
                          'remove' => __('port.groups.remove'),
                      ]">
            <x-slot:heading>
                <div class="tw:inline-flex tw:rounded-lg tw:border tw:border-gray-300 tw:dark:border-dark-gray-200 tw:overflow-hidden" role="tablist" aria-label="{{ __('port.groups.members') }}">
                    @foreach (['members' => __('port.groups.members'), 'available' => __('port.groups.available'), 'all' => __('port.groups.all')] as $key => $label)
                        <button type="button"
                                role="tab"
                                class="tw:px-3 tw:py-2 tw:border-0 tw:not-first:border-l tw:border-gray-300 tw:dark:border-dark-gray-200 tw:whitespace-nowrap tw:transition-colors"
                                :class="membership === '{{ $key }}' ? 'tw:bg-blue-600 tw:text-white' : 'tw:bg-white tw:dark:bg-dark-gray-500 tw:hover:bg-gray-50 tw:dark:hover:bg-dark-gray-400'"
                                :aria-selected="membership === '{{ $key }}'"
                                x-on:click="setMembership('{{ $key }}')">
                            {{ $label }}
                            @if ($key === 'members')
                                <span class="tw:ml-1 tw:px-1.5 tw:rounded-full tw:text-xs"
                                      :class="membership === 'members' ? 'tw:bg-white/25' : 'tw:bg-gray-200 tw:dark:bg-dark-gray-300'"
                                      x-text="members"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </x-slot:heading>

            <x-slot:actions>
                <button type="button" class="lnms-btn lnms-btn-primary tw:disabled:opacity-50" x-show="membership !== 'members'" x-on:click="bulk('add')" :disabled="isPending('update')">
                    <i class="fa fa-plus" aria-hidden="true"></i> {{ __('port.groups.add') }}
                </button>
                <button type="button" class="lnms-btn lnms-btn-danger tw:disabled:opacity-50" x-show="membership !== 'available'" x-on:click="bulk('remove')" :disabled="isPending('update')">
                    <i class="fa fa-minus" aria-hidden="true"></i> {{ __('port.groups.remove') }}
                </button>
            </x-slot:actions>

            <x-data-table.column field="device" :label="__('Device')" class="tw:whitespace-nowrap">
                <template x-if="row.device_url">
                    <a :href="row.device_url" x-data="deviceLink({device_id: row.device_id})" x-text="row.device"></a>
                </template>
                <span x-show="! row.device_url" class="tw:text-gray-500 tw:dark:text-dark-white-400">{{ __('port.groups.unknown_device') }}</span>
            </x-data-table.column>
            <x-data-table.column field="ifName" :label="__('Port')" :hideable="false">
                <a :href="row.url"
                   class="tw:font-semibold"
                   :class="row.link_class + (row.deleted ? ' tw:line-through' : '')"
                   x-data="portLink({port_id: row.port_id})"
                   x-text="row.label"></a>
                <div class="tw:text-gray-500 tw:dark:text-dark-white-400 tw:whitespace-nowrap" x-show="row.ifDescr && row.ifDescr !== row.label" x-text="row.ifDescr"></div>
            </x-data-table.column>
            <x-data-table.column field="ifOperStatus" :label="__('Status')" class="tw:whitespace-nowrap">
                <x-port.status-badge port="row" />
            </x-data-table.column>
            <x-data-table.column field="ifSpeed" :label="__('Speed')" class="tw:whitespace-nowrap" x-text="row.ifSpeed ? LibreNMS.Number.formatSi(row.ifSpeed, 2, 0, 'bps') : ''" />
            <x-data-table.column field="ifAlias" :label="__('Description')" class="tw:max-md:col-span-2" />
            <x-data-table.column field="member" :label="__('port.groups.member')" :sortable="false" :hideable="false" :visible="false">
                <span x-show="row.member" class="tw:text-green-700 tw:dark:text-green-400 tw:whitespace-nowrap">{{ __('port.groups.in_group') }}</span>
            </x-data-table.column>

            <x-slot:empty>{{ __('port.settings.no_ports') }}</x-slot:empty>

            <x-slot:after>
                <x-modal show="confirmAction" maxWidth="md">
                    <x-slot name="heading">
                        <h4 class="tw:m-0 tw:text-base tw:font-semibold" x-text="(confirmAction === 'add' ? lang.add : lang.remove) + '?'"></h4>
                    </x-slot>
                    <p class="tw:m-0" x-text="(confirmAction === 'add' ? lang.confirm_add : lang.confirm_remove).replace('__count__', total)"></p>
                    <x-slot name="footer">
                        <button type="button" class="lnms-btn lnms-btn-default tw:px-3 tw:py-1.5" x-on:click="confirmAction = null">{{ __('Cancel') }}</button>
                        <button type="button" class="lnms-btn tw:px-3 tw:py-1.5" :class="confirmAction === 'add' ? 'lnms-btn-primary' : 'lnms-btn-danger'" x-on:click="apply(confirmAction)" :disabled="isPending('update')">{{ __('Apply') }}</button>
                    </x-slot>
                </x-modal>
            </x-slot:after>
        </x-data-table>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('portGroupPorts', (config) => LibreNMS.dataTable(config, {
                membership: config.membership,
                members: config.members,
                confirmAction: null,

                init() {
                    this.setColumnVisible('member', this.membership === 'all');
                },

                requestParams() {
                    return {membership: this.membership};
                },

                loaded(json) {
                    this.members = json.members;
                },

                setMembership(membership) {
                    if (membership === this.membership) return;
                    this.membership = membership;
                    this.setColumnVisible('member', membership === 'all');
                    this.clearSelection();
                    this.changed(true);
                },

                bulk(action) {
                    if (this.allMatching) {
                        this.confirmAction = action;
                        return;
                    }

                    this.apply(action);
                },

                apply(action) {
                    if (this.isPending('update')) return;

                    const data = {action: action, membership: this.membership, ...this.selectionData()};

                    this.request('update', 'POST', config.updateUrl, data)
                        .then((response) => {
                            this.confirmAction = null;
                            this.members = response.members;
                            this.clearSelection();
                            this.notify(response.message);
                            this.load();
                        })
                        .catch(() => {});
                },
            }));
        });
    </script>
@endpush
