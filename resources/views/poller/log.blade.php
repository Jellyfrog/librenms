@extends('poller.index')

@section('title', __('Poller Log'))

@section('content')

@parent

<x-data-table name="poller-log"
              :url="route('table.poller-log')"
              :params="array_filter(['unpolled' => $filter == 'unpolled' ? 1 : null])"
              :sort="['last_polled_timetaken' => 'desc']"
              :column-toggle="false"
              :panel="false">
    <x-slot:heading>
        <a href="{{ route('poller.log') }}" @class(['btn btn-primary btn-sm', 'active' => $filter != 'unpolled'])>{{ __('All devices') }}</a>
        <a href="{{ route('poller.log', ['filter' => 'unpolled']) }}" @class(['btn btn-danger btn-sm', 'active' => $filter == 'unpolled'])>{{ __('Unpolled devices') }}</a>
    </x-slot:heading>
    <x-data-table.column field="hostname" :label="__('Hostname')" html />
    <x-data-table.column field="last_polled" :label="__('Last Polled')" />
    <x-data-table.column field="poller_group" :label="__('Poller Group')" />
    <x-data-table.column field="location" :label="__('Location')" />
    <x-data-table.column field="last_polled_timetaken" :label="__('Polling Duration') . ' (' . __('Seconds') . ')'" />
</x-data-table>
@endsection
