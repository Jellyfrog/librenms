@extends('layouts.librenmsv1')

@section('title', __('Vlans'))

@section('content')
    <div class="container-fluid">
        <x-panel>
            <x-slot:heading>
                <h2 class="panel-title">{{ __('VLAN') }}
                {{-- both tables follow the selected vlan --}}
                <select id="vlan-select" x-data x-on:change="window.dispatchEvent(new CustomEvent('vlan:select', {detail: $el.value}))">
                    @foreach($vlanIds as $vlanId)
                        <option>{{ $vlanId }}</option>
                    @endforeach
                </select>
                </h2>
            </x-slot:heading>
            <x-slot:slot class="tw:p-0!">
            <x-tabs>
                <x-tab name="{{ __('Devices') }}">
                    <x-data-table name="vlan-devices"
                                  :url="route('table.vlan-devices')"
                                  :params="['vlan' => (int) $vlanIds->first()]"
                                  :sort="['device' => 'asc']"
                                  :panel="false"
                                  x-on:vlan:select.window="config.params.vlan = $event.detail; changed(true)">
                        <x-data-table.column field="device" :label="__('Device')" html />
                        <x-data-table.column field="ports_count" :label="__('Ports')" />
                        <x-data-table.column field="name" :label="__('Local Name')" />
                        <x-data-table.column field="domain" :label="__('Domain')" :visible="false" />
                        <x-data-table.column field="type" :label="__('Type')" />
                        <x-data-table.column field="state" :label="__('State')">
                            <span :class="row.state ? 'text-success' : 'text-danger'" x-text="row.state ? @js(__('operational')) : @js(__('suspended'))"></span>
                        </x-data-table.column>
                    </x-data-table>
                </x-tab>
                <x-tab value="image" name="{{ __('Ports') }}">
                    <x-data-table name="vlan-ports"
                                  :url="route('table.vlan-ports')"
                                  :params="['vlan' => (int) $vlanIds->first()]"
                                  :sort="['device' => 'asc', 'port' => 'asc']"
                                  :panel="false"
                                  x-on:vlan:select.window="config.params.vlan = $event.detail; changed(true)">
                        <x-data-table.column field="device" :label="__('Device')" html />
                        <x-data-table.column field="port" :label="__('Port')" html />
                        <x-data-table.column field="untagged" :label="__('Untagged')" />
                        <x-data-table.column field="state" :label="__('State')" />
                        <x-data-table.column field="cost" :label="__('Cost')" />
                    </x-data-table>
                </x-tab>
            </x-tabs>
            </x-slot:slot>
        </x-panel>
    </div>
@endsection
