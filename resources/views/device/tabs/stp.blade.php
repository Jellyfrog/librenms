@extends('layouts.librenmsv1')

@section('content')
    <x-device.page :device="$device">
    <x-option-bar name="{{ trans('stp.vlan') }}" :options="$data['vlans']" :selected="$data['vlan']"></x-option-bar>

    @foreach($data['stpInstances'] as $instance)
        <x-panel class="stp-panel">
            <x-slot name="title"><span class="tw:font-bold">{{ trans('stp.stp_info') }}</span></x-slot>
            <table class="table table-condensed table-striped table-hover">
                <tr>
                    <td>{{ trans('stp.root_bridge') }}</td>
                    <td>{{ $instance['rootBridge'] ? __('Yes') : __('No') }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.bridge_address') }}</td>
                    <td>
                        {{ \LibreNMS\Util\Mac::parse($instance['bridgeAddress'])->readable() }}
                        @if($url = \LibreNMS\Util\Url::deviceLink(\App\Facades\DeviceCache::get(\App\Models\Stp::where('bridgeAddress', $instance['bridgeAddress'])->value('device_id'))))
                            ({!! $url !!})
                        @elseif($brVendor = \LibreNMS\Util\Mac::parse($instance['bridgeAddress'])->vendor())
                            ({{ $brVendor }})
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>{{ trans('stp.protocol') }}</td>
                    <td>{{ $instance['protocolSpecification'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.priority') }}</td>
                    <td>{{ $instance['priority'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.last_topology_change') }}</td>
                    <td>{{ \LibreNMS\Util\Time::formatInterval($instance['timeSinceTopologyChange']) }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.topology_changes') }}</td>
                    <td>{{ $instance['topChanges'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.designated_root') }}</td>
                    <td>
                        {{ \LibreNMS\Util\Mac::parse($instance['designatedRoot'])->readable() }}
                        @if($url = \LibreNMS\Util\Url::deviceLink(\App\Facades\DeviceCache::get(\App\Models\Stp::where('bridgeAddress', $instance['designatedRoot'])->whereNot('bridgeAddress', '')->value('device_id'))))
                            ({!! $url !!})
                        @elseif($drVendor = \LibreNMS\Util\Mac::parse($instance['designatedRoot'])->vendor())
                            ({{ $drVendor }})
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>{{ trans('stp.root_cost') }}</td>
                    <td>{{ $instance['rootCost'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.root_port') }}</td>
                    <td>{{ $instance['rootPort'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.max_age') }}</td>
                    <td>{{ $instance['maxAge'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.hello_time') }}</td>
                    <td>{{ $instance['helloTime'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.hold_time') }}</td>
                    <td>{{ $instance['holdTime'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.forward_delay') }}</td>
                    <td>{{ $instance['forwardDelay'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.bridge_max_age') }}</td>
                    <td>{{ $instance['bridgeMaxAge'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.bridge_hello_time') }}</td>
                    <td>{{ $instance['bridgeHelloTime'] }}</td>
                </tr>
                <tr>
                    <td>{{ trans('stp.bridge_forward_delay') }}</td>
                    <td>{{ $instance['bridgeForwardDelay'] }}</td>
                </tr>
            </table>
        </x-panel>
    @endforeach

    @if($data['stpPorts'])
        <x-data-table name="stp-ports"
                      :url="route('table.port-stp')"
                      :params="array_filter(['device_id' => $data['device_id'], 'vlan' => $data['vlan']])"
                      :sort="['port_id' => 'asc']"
                      :search="false"
                      :title="trans('stp.stp_ports')">
            <x-data-table.column field="port_id" :label="trans('stp.port')" :hideable="false" html />
            <x-data-table.column field="vlan" :label="trans('stp.vlan')" :visible="false" />
            <x-data-table.column field="priority" :label="trans('stp.priority')" />
            <x-data-table.column field="state" :label="trans('stp.state')" />
            <x-data-table.column field="enable" :label="trans('stp.enable')" />
            <x-data-table.column field="pathCost" :label="trans('stp.path_cost')" />
            @foreach(['designatedRoot' => trans('stp.designated_root_short'), 'designatedCost' => trans('stp.designated_cost'), 'designatedBridge' => trans('stp.designated_bridge')] as $field => $label)
                @if($field === 'designatedCost')
                    <x-data-table.column :field="$field" :label="$label" />
                @else
                    <x-data-table.column :field="$field" :label="$label">
                        <div x-html="row.{{ $field }}_device"></div>
                        <span :title="row.{{ $field }}_vendor" x-text="row.{{ $field }}"></span>
                    </x-data-table.column>
                @endif
            @endforeach
            <x-data-table.column field="designatedPort" :label="trans('stp.designated_port')" />
            <x-data-table.column field="forwardTransitions" :label="trans('stp.forward_transitions')" />
        </x-data-table>
    @endif
</x-device.page>
@endsection

@push('styles')
    <style>
        .stp-panel .panel-body {
            padding: 0;
            margin-top: -1px;
        }

        .stp-panel .table {
            margin-bottom: 0;
        }
    </style>
@endpush
