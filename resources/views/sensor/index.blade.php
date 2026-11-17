@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table :name="'sensors-' . $metric" :url="route('table.sensors')" :params="['view' => $view, 'class' => $metric, 'status' => $status]" :per-page-options="[50, 100, 250, -1]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" name="{{ __('Status') }}" :options="$status_bar" :selected="$status"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="device_hostname" :label="__('Device')" x-html="row.device_hostname" />
        <x-data-table.column field="sensor_descr" :label="__('Sensor')" x-html="row.sensor_descr" />
        <x-data-table.column field="graph" :label="''" :sortable="false" x-html="row.graph" />
        <x-data-table.column field="alert" :label="''" :sortable="false" x-html="row.alert" />
        <x-data-table.column field="sensor_current" :label="__('Current')" x-html="row.sensor_current" />
        <x-data-table.column field="sensor_limit_low" :label="__('Low Limit')" x-html="row.sensor_limit_low" />
        <x-data-table.column field="sensor_limit" :label="__('High Limit')" x-html="row.sensor_limit" />
    </x-data-table>
@endsection
