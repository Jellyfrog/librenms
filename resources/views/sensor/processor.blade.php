@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table name="processors" :url="route('table.processors')" :export="route('table.processors.export')" :params="['view' => $view, 'status' => $status]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" name="{{ __('Status') }}" :options="$status_bar" :selected="$status"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="device_hostname" :label="__('Device')" html />
        <x-data-table.column field="processor_descr" :label="__('Processor')" html />
        <x-data-table.column field="graph" :sortable="false" html />
        <x-data-table.column field="processor_usage" :label="__('Usage')" html />
    </x-data-table>
@endsection
