@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table :name="'processors'" :url="route('table.processors')" :export="route('table.processors.export')" :params="['view' => $view, 'status' => $status]" :per-page-options="[50, 100, 250, -1]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" name="{{ __('Status') }}" :options="$status_bar" :selected="$status"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="device_hostname" :label="__('Device')" x-html="row.device_hostname" />
        <x-data-table.column field="processor_descr" :label="__('Processor')" x-html="row.processor_descr" />
        <x-data-table.column field="graph" :label="''" :sortable="false" x-html="row.graph" />
        <x-data-table.column field="processor_usage" :label="__('Usage')" x-html="row.processor_usage" />
    </x-data-table>
@endsection
