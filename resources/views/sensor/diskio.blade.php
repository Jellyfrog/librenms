@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table :name="'diskio'" :url="route('table.diskio')" :params="['view' => $view]" :per-page-options="[50, 100, 250, -1]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="device_hostname" :label="__('Device')" x-html="row.device_hostname" />
        <x-data-table.column field="diskio_descr" :label="__('Storage')" x-html="row.diskio_descr" />
        <x-data-table.column field="bits_graph" :label="__('Bits')" :sortable="false" x-html="row.bits_graph" />
        <x-data-table.column field="ops_graph" :label="__('Ops')" :sortable="false" x-html="row.ops_graph" />
    </x-data-table>
@endsection
