@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table :name="'mempools'" :url="route('table.mempools')" export :params="['view' => $view, 'status' => $status]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" name="{{ __('Status') }}" :options="$status_bar" :selected="$status"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="hostname" :label="__('Device')" html />
        <x-data-table.column field="mempool_descr" :label="$view == 'graphs' ? '' : __('Memory')" html />
        <x-data-table.column field="graph" :label="''" :sortable="false" html />
        <x-data-table.column field="mempool_used" :label="$view == 'graphs' ? '' : __('Used')" html />
        <x-data-table.column field="mempool_perc" :label="$view == 'graphs' ? '' : __('Usage')" html />
    </x-data-table>
@endsection
