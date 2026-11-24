@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table name="storages" :url="route('table.storages')" :export="route('table.storages.export')" :params="['view' => $view, 'status' => $status]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" name="{{ __('Status') }}" :options="$status_bar" :selected="$status"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="device_hostname" :label="__('Device')" html />
        <x-data-table.column field="storage_descr" :label="__('Storage')" html />
        <x-data-table.column field="graph" :sortable="false" html />
        <x-data-table.column field="storage_used" :label="__('Used')" html />
        <x-data-table.column field="storage_perc" :label="__('Usage')" html />
    </x-data-table>
@endsection
