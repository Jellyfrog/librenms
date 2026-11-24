@extends('layouts.librenmsv1')

@section('content')
<x-device.page :device="$device">
    <x-data-table name="tnmsne"
                  :url="route('table.tnmsne')"
                  :params="['device_id' => $device->device_id]"
                  :sort="['neName' => 'asc']"
                  :title="__('Coriant NE Hardware')">
        <x-data-table.column field="neName" :label="__('Name')" />
        <x-data-table.column field="neLocation" :label="__('Location')" />
        <x-data-table.column field="neType" :label="__('Type')" />
        @foreach(['neOpMode' => __('Operation Mode'), 'neAlarm' => __('Alarm'), 'neOpState' => __('State')] as $field => $label)
            <x-data-table.column :field="$field" :label="$label">
                <span class="label tw:inline-block tw:min-w-10" :class="row.{{ $field }}_class" x-text="row.{{ $field }}"></span>
            </x-data-table.column>
        @endforeach
    </x-data-table>
</x-device.page>
@endsection
