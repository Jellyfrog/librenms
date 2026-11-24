@extends('layouts.librenmsv1')

@section('title', __('SSL Certificates'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <x-data-table name="ssl-certificates"
                          :url="route('table.ssl-certificates')"
                          :sort="['host' => 'asc']"
                          :per-page-options="[25, 50, 100, -1]"
                          :title="__('SSL Certificates')">
                @can('create', \App\Models\SslCertificate::class)
                    <x-slot:heading>
                        <a href="{{ route('ssl-certificates.create') }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus fa-fw" aria-hidden="true"></i> {{ __('Add Certificate') }}
                        </a>
                    </x-slot:heading>
                @endcan
                <x-data-table.column field="host" :label="__('Host')" />
                <x-data-table.column field="port" :label="__('Port')" />
                <x-data-table.column field="subject" :label="__('Subject')" />
                <x-data-table.column field="issuer" :label="__('Issuer')" />
                <x-data-table.column field="valid_to" :label="__('Valid Until')" />
                <x-data-table.column field="days_until_expiry" :label="__('Days Until Expiry')" html />
                <x-data-table.column field="device" :label="__('Device')" :sortable="false" html />
                <x-data-table.column field="status" :label="__('Status')" :sortable="false" html />
                <x-data-table.column field="actions" :label="__('Actions')" :sortable="false" class="tw:whitespace-nowrap">
                    <a :href="@js(url('ssl-certificates')) + '/' + row.id" class="btn btn-xs btn-info"><i class="fa fa-eye fa-fw"></i> {{ __('View') }}</a>
                    @can('ssl-certificate.update')
                        <button type="button" class="btn btn-xs" :class="row.disabled ? 'btn-success' : 'btn-warning'" :disabled="isPending('ssl' + row.id)"
                                x-on:click="request('ssl' + row.id, 'PUT', route('ssl-certificates.update', {ssl_certificate: row.id}), {disabled: row.disabled ? 0 : 1})
                                    .then(() => { toastr.success(row.disabled ? @js(__('Certificate enabled')) : @js(__('Certificate paused'))); reload(); }).catch(() => {})">
                            <i class="fa fa-fw" :class="row.disabled ? 'fa-play' : 'fa-pause'"></i>
                            <span x-text="row.disabled ? @js(__('Enable')) : @js(__('Pause'))"></span>
                        </button>
                    @endcan
                    @can('ssl-certificate.delete')
                        <button type="button" class="btn btn-xs btn-danger" :disabled="isPending('ssl' + row.id)"
                                x-on:click="confirm(@js(__('Delete this certificate?'))) && request('ssl' + row.id, 'DELETE', route('ssl-certificates.destroy', {ssl_certificate: row.id}))
                                    .then(() => { toastr.success(@js(__('Certificate deleted'))); reload(); }).catch(() => {})">
                            <i class="fa fa-trash fa-fw"></i> {{ __('Delete') }}
                        </button>
                    @endcan
                </x-data-table.column>
            </x-data-table>
        </div>
    </div>
</div>
@endsection
