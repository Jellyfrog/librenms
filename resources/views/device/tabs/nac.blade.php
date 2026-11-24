@extends('layouts.librenmsv1')

@section('content')
    <x-device.page :device="$device">
    @isset($data['submenu'])
        <x-submenu :title="$title" :menu="$data['submenu']" :device-id="$device_id" :current-tab="$current_tab" :selected="$vars" />
    @endisset

    <x-nac-table :device-id="$device->device_id" />
    </x-device.page>
@endsection
