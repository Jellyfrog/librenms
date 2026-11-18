@extends('layouts.librenmsv1')

@section('content')
    <x-device.page :device="$device" subtitle="{{ __('Eventlog') }}">
        <x-device.log-tabs :device="$device" tab="eventlog" />

        <x-eventlog-table :device-id="$device->device_id" :filter="$filter" />
    </x-device.page>
@endsection
