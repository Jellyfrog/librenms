@extends('layouts.librenmsv1')

@section('title', __('Virtual Machines'))

@section('content')
<div class="container-fluid">
    <x-option-bar name="{{ __('View') }}" :options="$nav" :selected="$graph ?? 'list'" linkClass="sync-filter-url"></x-option-bar>
    @if($graph)
        <x-vminfo-graphs :vms="$vms" :graph="$graph" :filter-fields="$filterFields" :filter="$filter" :from="request('from')" :to="request('to')"/>
    @else
        <x-vminfo-table :filter-fields="$filterFields" :filter="$filter" />
    @endif
</div>
@endsection
