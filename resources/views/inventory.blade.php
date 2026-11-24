@extends('layouts.librenmsv1')

@section('title', __('Inventory'))

@section('content')
    <div class="container-fluid">
        <x-data-table name="inventory"
                      :url="route('table.inventory')"
                      :sort="['device' => 'asc']"
                      filter-name="inventory"
                      :filter-fields="$filterFields"
                      :filter="$filter"
                      :title="__('Inventory')">
            @if($show_purge)
                <x-slot:heading>
                    <a href="{{ route('inventory.purge') }}"><i class="fa fa-trash"></i> {{ __('inventory.purge') }}</a>
                </x-slot:heading>
            @endif
            <x-data-table.column field="device" :label="__('Device')" html />
            <x-data-table.column field="descr" :label="__('Description')" />
            <x-data-table.column field="name" :label="__('inventory.name')" />
            <x-data-table.column field="model" :label="__('inventory.model')" />
            <x-data-table.column field="serial" :label="__('inventory.serial')" />
        </x-data-table>
    </div>
@endsection
