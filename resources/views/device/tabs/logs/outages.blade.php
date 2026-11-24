@extends('layouts.librenmsv1')

@section('content')
    <x-device.page :device="$device" subtitle="{{ __('Outages') }}">
        <x-device.log-tabs :device="$device" tab="outages"/>

        <x-outages-table name="outages-device" :params="['device' => $device->device_id, 'from' => $from, 'to' => $to]" />
    </x-device.page>
@endsection
