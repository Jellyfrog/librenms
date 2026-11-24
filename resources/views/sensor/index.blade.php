@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table :name="'sensors-' . $metric" :url="route('table.sensors')" :export="route('table.sensors.export')" :params="['view' => $view, 'class' => $metric, 'status' => $status]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" name="{{ __('Status') }}" :options="$status_bar" :selected="$status"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="device_hostname" :label="__('Device')" html />
        <x-data-table.column field="sensor_descr" :label="__('Sensor')" html />
        <x-data-table.column field="graph" :sortable="false" html />
        <x-data-table.column field="alert" :sortable="false" html />
        <x-data-table.column field="sensor_current" :label="__('Current')" html />
        <x-data-table.column field="sensor_limit_low" :label="__('Low Limit')" html />
        <x-data-table.column field="sensor_limit" :label="__('High Limit')" html />
    </x-data-table>
@endsection
