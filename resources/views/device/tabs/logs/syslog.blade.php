@extends('layouts.librenmsv1')

@section('content')
    <x-device.page :device="$device" subtitle="{{ __('Syslog') }}">
        <x-device.log-tabs :device="$device" tab="syslog"/>

        <x-syslog-table :device-id="$device->device_id" :filter="$filter" />
    </x-device.page>
@endsection
