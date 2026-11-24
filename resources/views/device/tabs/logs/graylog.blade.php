@extends('layouts.librenmsv1')

@section('content')
    <x-device.page :device="$device" subtitle="{{ __('Graylog') }}">
        <x-device.log-tabs :device="$device" tab="graylog" />

        <x-graylog-table :device-id="$device->device_id" :filter="$filter" />
    </x-device.page>
@endsection
