@extends('layouts.librenmsv1')

@section('content')
    <x-device.page :device="$device" :subtitle="__('Routing Table')">
        <x-device.routing-tabs :device="$device" tab="routes" />

        <x-data-table name="routes"
                      :url="route('table.routes')"
                      :params="['device_id' => $device->device_id]"
                      :sort="['inetCidrRouteDest' => 'asc']">
            <x-slot:heading>
                <select class="form-control input-sm tw:w-auto" aria-label="{{ __('Proto') }}"
                        x-on:change="config.params.protocol = $event.target.value; changed(true)">
                    <option value="">{{ __('all Protocols') }}</option>
                    <option value="ipv4">{{ __('IPv4 only') }}</option>
                    <option value="ipv6">{{ __('IPv6 only') }}</option>
                </select>
                <label class="tw:flex tw:items-center tw:gap-2 tw:m-0 tw:font-normal">
                    <x-toggle x-on:change="config.params.historical = $event.target.checked ? 1 : 0; changed(true)" />
                    {{ __('Include historical routes in the table') }}
                </label>
            </x-slot:heading>
            <x-data-table.column field="context_name" :label="__('VRF')" width="125px" html />
            <x-data-table.column field="inetCidrRouteDestType" :label="__('Proto')" width="70px" />
            <x-data-table.column field="inetCidrRouteDest" :label="__('Destination')" />
            <x-data-table.column field="inetCidrRoutePfxLen" :label="__('Mask')" width="80px" />
            <x-data-table.column field="inetCidrRouteNextHop" :label="__('Next hop')" html />
            <x-data-table.column field="inetCidrRouteIfIndex" :label="__('Interface')" html />
            <x-data-table.column field="inetCidrRouteMetric1" :label="__('Metric')" width="85px" />
            <x-data-table.column field="inetCidrRouteType" :label="__('Type')" width="85px" />
            <x-data-table.column field="inetCidrRouteProto" :label="__('Proto')" width="85px" />
            <x-data-table.column field="created_at" :label="__('First seen')" width="165px" />
            <x-data-table.column field="updated_at" :label="__('Last seen')" width="165px" />
            <x-slot:after>
                <div class="alert alert-info tw:mt-4">
                    {{ __('Warning: Routing Table is only retrieved during device discovery. Devices are skipped if they have more than :max routes.', ['max' => $max_routes]) }}
                </div>
            </x-slot:after>
        </x-data-table>
    </x-device.page>
@endsection
