@extends('layouts.librenmsv1')

@section('title', __('Billing'))

@section('content')
<div class="container-fluid" x-data="{ createBill: @js($port !== null || $errors->any()) }" x-on:create-bill.window="createBill = true">
    <x-data-table name="bills"
                  :url="route('table.bills')"
                  :params="array_filter(['bill_type' => $filters['bill_type'], 'state' => $filters['state']])"
                  :sort="['bill_name' => 'asc']"
                  :column-toggle="false">
        <x-slot:heading>
            @can('create', \App\Models\Bill::class)
                <button type="button" class="btn btn-default btn-sm" x-on:click="$dispatch('create-bill')"><i class="fa fa-plus"></i> {{ __('Create Bill') }}</button>
            @endcan
            <select class="form-control input-sm tw:w-auto" aria-label="{{ __('Billing period') }}" x-on:change="config.params.period = $event.target.value; changed(true)">
                <option value="">{{ __('Current Billing Period') }}</option>
                <option value="prev">{{ __('Previous Billing Period') }}</option>
            </select>
            <select class="form-control input-sm tw:w-auto" aria-label="{{ __('Type') }}" x-on:change="config.params.bill_type = $event.target.value; changed(true)">
                <option value="">{{ __('All Types') }}</option>
                <option value="cdr" @selected($filters['bill_type'] === 'cdr')>{{ __('CDR') }}</option>
                <option value="quota" @selected($filters['bill_type'] === 'quota')>{{ __('Quota') }}</option>
            </select>
            <select class="form-control input-sm tw:w-auto" aria-label="{{ __('State') }}" x-on:change="config.params.state = $event.target.value; changed(true)">
                <option value="">{{ __('All States') }}</option>
                <option value="under" @selected($filters['state'] === 'under')>{{ __('Under Quota') }}</option>
                <option value="over" @selected($filters['state'] === 'over')>{{ __('Over Quota') }}</option>
            </select>
        </x-slot:heading>
        <x-data-table.column field="bill_name" :label="__('Billing name')" :hideable="false" html />
        <x-data-table.column field="notes" :sortable="false" />
        <x-data-table.column field="bill_type" :label="__('Type')" />
        <x-data-table.column field="bill_allowed" :label="__('Allowed')" class="tw:text-right" />
        <x-data-table.column field="total_data_in" :label="__('Inbound')" class="tw:text-right" />
        <x-data-table.column field="total_data_out" :label="__('Outbound')" class="tw:text-right" />
        <x-data-table.column field="total_data" :label="__('Total')" class="tw:text-right" html />
        <x-data-table.column field="rate_95th" :label="__('95th Percentile')" class="tw:text-right" html />
        <x-data-table.column field="overusage" :label="__('Overusage')" :sortable="false" class="tw:text-center" html />
        <x-data-table.column field="predicted" :label="__('Predicted')" :sortable="false" class="tw:text-center" />
        <x-data-table.column field="graph" :sortable="false" html />
        <x-data-table.column field="actions" :sortable="false" html />
    </x-data-table>

    @can('create', \App\Models\Bill::class)
        <x-modal show="createBill" :title="__('Add Traffic Bill')" maxWidth="2xl">
            <form method="post" action="{{ route('bill.store') }}" class="form-horizontal">
                @csrf
                <div class="form-group">
                    <label class="col-sm-4 control-label" for="device">{{ __('Device') }}</label>
                    <div class="col-sm-8">
                        <select class="form-control input-sm" id="device"></select>
                    </div>
                </div>
                <div class="form-group @error('port_id') has-error @enderror">
                    <label class="col-sm-4 control-label" for="port_id">{{ __('Port') }}</label>
                    <div class="col-sm-8">
                        <select class="form-control input-sm" id="port_id" name="port_id"></select>
                        <span class="help-block">{{ $errors->first('port_id') }}</span>
                    </div>
                </div>
                @include('bill.form', ['bill' => $newBill])
                <div class="form-group">
                    <div class="col-sm-offset-4 col-sm-8">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> {{ __('Add Bill') }}</button>
                    </div>
                </div>
            </form>
        </x-modal>
    @endcan
</div>


@endsection

@section('javascript')
<script>
    $(function () {
        @can('create', \App\Models\Bill::class)
        init_select2('#device', 'device', {}, @js($port?->device ? ['id' => $port->device_id, 'text' => $port->device->display] : null), @js(__('Select Device')), {width: '100%'});
        init_select2('#port_id', 'port', function (params) {
            params.device = $('#device').val();
            return params;
        }, @js($port ? ['id' => $port->port_id, 'text' => $port->getLabel()] : null), @js(__('Select Port')), {width: '100%'});
        $('#device').on('change', function () {
            $('#port_id').val(null).trigger('change');
        });
        @endcan
    });
</script>
@endsection
