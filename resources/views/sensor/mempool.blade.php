@extends('layouts.librenmsv1')

@section('title', $title)

@section('content')
    <x-data-table :name="'mempools'" :url="route('table.mempools')" :export="route('table.mempools.export')" :params="['view' => $view, 'status' => $status]" :per-page-options="[50, 100, 250, -1]">
        <x-slot name="heading">
            <x-option-bar border="none" name="{{ __('Health') }}" :options="$metrics" :selected="$metric"></x-option-bar>
            <x-option-bar border="none" name="{{ __('Status') }}" :options="$status_bar" :selected="$status"></x-option-bar>
            <x-option-bar border="none" :options="$views" :selected="$view"></x-option-bar>
        </x-slot>
        <x-data-table.column field="hostname" :label="__('Device')" x-html="row.hostname" />
        <x-data-table.column field="mempool_descr" :label="$view == 'graphs' ? '' : __('Memory')" x-html="row.mempool_descr" />
        <x-data-table.column field="graph" :label="''" :sortable="false" x-html="row.graph" />
        <x-data-table.column field="mempool_used" :label="$view == 'graphs' ? '' : __('Used')" x-html="row.mempool_used" />
        <x-data-table.column field="mempool_perc" :label="$view == 'graphs' ? '' : __('Usage')" x-html="row.mempool_perc" />
    </x-data-table>
@endsection
