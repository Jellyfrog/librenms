@extends('layouts.librenmsv1')

@section('content')
    <x-device.page :device="$device">
        <x-option-bar name="{{ __('View') }}" :options="$data['nav']" :selected="$data['graph'] ?? 'list'" linkClass="sync-filter-url"></x-option-bar>
        @if($data['graph'])
            <x-vminfo-graphs :vms="$data['vms']" :graph="$data['graph']" :filter-fields="$data['filterFields']" :filter="$data['filter']" :from="request('from')" :to="request('to')"/>
        @else
            <x-vminfo-table :device-id="$device->device_id" :filter-fields="$data['filterFields']" :filter="$data['filter']" />
        @endif
    </x-device.page>
@endsection
