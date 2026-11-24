@extends('layouts.librenmsv1')

@section('title', __('Port Security'))

@section('content')
<div class="container-fluid">
    <x-port-security-table :filter-fields="$filterFields" :filter="$filter" />
</div>
@endsection
