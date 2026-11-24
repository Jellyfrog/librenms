@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table :name="'diskio'" :url="route('table.diskio')" :params="['view' => $view]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="device_hostname" :label="__('Device')" html />
        <x-data-table.column field="diskio_descr" :label="__('Storage')" />
        <x-data-table.column field="bits_graph" :label="__('Bits')" :sortable="false" html />
        <x-data-table.column field="ops_graph" :label="__('Ops')" :sortable="false" html />
    </x-data-table>
@endsection
