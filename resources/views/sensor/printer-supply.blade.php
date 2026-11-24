@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table name="printer-supply" :url="route('table.printer-supply')" :params="['view' => $view]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="device_hostname" :label="__('Device')" html />
        <x-data-table.column field="supply_descr" :label="__('Descr')" html />
        <x-data-table.column field="supply_type" :label="__('Type')" html />
        <x-data-table.column field="graph" :sortable="false" html />
        <x-data-table.column field="supply_current" :label="__('Supply')" html />
    </x-data-table>
@endsection
