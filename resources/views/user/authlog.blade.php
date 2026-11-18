@extends('layouts.librenmsv1')

@section('title', __('Authlog'))

@section('content')
<div class="container-fluid">
    <x-data-table name="authlog" mode="local" :rows="$rows" :sort="['datetime' => 'desc']" :title="__('Authlog')">
        <x-data-table.column field="datetime" :label="__('Timestamp')" />
        <x-data-table.column field="user" :label="__('User')" />
        <x-data-table.column field="address" :label="__('IP Address')" />
        <x-data-table.column field="result" :label="__('Result')" />
    </x-data-table>
</div>
@endsection
