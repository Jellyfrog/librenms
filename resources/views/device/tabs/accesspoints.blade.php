@extends('layouts.librenmsv1')

@section('content')
    <x-device.page :device="$device">
        <x-data-table name="access-points"
                      :url="route('table.access-points')"
                      :params="['device_id' => $device->device_id]"
                      :sort="['name' => 'asc']"
                      :title="__('Access Points')">
            <x-data-table.column field="name" :label="__('Access Point')" :hideable="false" html />
            <x-data-table.column field="radio" :label="__('Radio')" html />
            <x-data-table.column field="channel" :label="__('Channel')" />
            <x-data-table.column field="numasoclients" :label="__('Clients')" />
            <x-data-table.column field="radioutil" :label="__('Utilization')" x-text="row.radioutil + '%'" />
            <x-data-table.column field="interference" :label="__('Interference Index')" />
            <x-data-table.column field="txpow" :label="__('Transmit Power')" />
            <x-data-table.column field="trends" :label="__('Trends')" :sortable="false" html />
        </x-data-table>
    </x-device.page>
@endsection
