@extends('layouts.librenmsv1')

@section('title', __('Outages'))

@php
$refresh = request()->input('refresh', 30);
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 col-lg-8 col-lg-offset-2">
            <x-outages-table :params="['device' => $device?->device_id, 'status' => $status, 'from' => $from, 'to' => $to]">
                <x-slot:heading>
                    @if($show_device_list)
                        <select class="form-control" aria-label="{{ __('Device') }}"
                                x-init="init_select2($el, 'device', {}, @js($selected_device), @js(__('device.all_devices'))); $($el).on('change', () => { config.params.device = $el.value; changed(true); })">
                            <option value="">{{ __('device.all_devices') }}</option>
                        </select>
                    @endif
                    <select class="form-control" aria-label="{{ __('Status') }}"
                            x-on:change="config.params.status = $event.target.value; setColumnVisible('up_again', $event.target.value !== 'current'); changed(true)">
                        @foreach(['current' => __('Current'), 'previous' => __('Previous'), 'all' => __('All')] as $value => $label)
                            <option value="{{ $value }}" @selected($status == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-date-range-picker
                        id="date_range" name="date_range"
                        start="{{ $from }}"
                        end="{{ $to }}"
                        class="form-control tw:min-w-64"
                        x-on:date-range-changed="let range = document.getElementById('date_range').dateRangePicker.get(); config.params.from = LibreNMS.Date.toBackend(range.start); config.params.to = LibreNMS.Date.toBackend(range.end); changed(true)"
                    ></x-date-range-picker>
                    {{-- put the current filters in the url to share or bookmark them --}}
                    <button type="button" class="btn btn-default"
                            x-on:click="let url = new URL(window.location.href); ['device', 'status', 'from', 'to'].forEach((key) => config.params[key] ? url.searchParams.set(key, config.params[key]) : url.searchParams.delete(key)); window.history.pushState({}, '', url)">{{ __('Update URL') }}</button>
                </x-slot:heading>
            </x-outages-table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<x-refresh-timer :refresh="$refresh" callback="refreshOutagesGrid"></x-refresh-timer>
<script>
    function refreshOutagesGrid() {
        window.dispatchEvent(new CustomEvent('data-table:reload', {detail: {name: 'outages'}}));
    }
</script>
@endsection
