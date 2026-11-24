@extends('layouts.librenmsv1')

@section('title', __('Locations'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">

    <x-data-table name="locations"
                  id="locations"
                  :url="route('table.location')"
                  export
                  :sort="['location' => 'asc']"
                  :per-page-options="[25, 50, 100, -1]"
                  :title="__('Locations')">
        <x-data-table.column field="location" :label="__('Location')" :hideable="false">
            <a :href="route('devices', {filter: {location_id: {eq: row.id}}})" x-text="row.location"></a>
        </x-data-table.column>
        <x-data-table.column field="coordinates" :label="__('Coordinates')" :sortable="false" class="coordinates-field">
            {{-- click to edit, enter or leaving the field saves "lat, lng", escape cancels --}}
            <template x-if="! row.editing">
                <span x-on:click="row.editing = true" class="tw:cursor-pointer">
                    <span class="coordinates-text" x-text="row.lat && row.lng ? row.lat + ', ' + row.lng : @js(__('N/A'))"></span> <i class="fa fa-pencil"></i>
                </span>
            </template>
            <template x-if="row.editing">
                <input type="text" class="form-control input-sm" x-init="$el.value = row.lat && row.lng ? row.lat + ', ' + row.lng : ''; $el.select()"
                       x-on:keydown.escape="row.editing = false"
                       x-on:keydown.enter="$el.blur()"
                       x-on:blur="let loc = $el.value.split(',', 2); row.editing = false; if (loc.length === 2) update_location(row.id, {lat: loc[0].trim(), lng: loc[1].trim()}, (success) => success && reload())">
            </template>
        </x-data-table.column>
        <x-data-table.column field="devices" :label="__('Devices')">
            <span class="label label-primary" x-text="row.devices"></span>
        </x-data-table.column>
        <x-data-table.column field="down" :label="__('Down')">
            <span class="label" :class="row.down > 0 ? 'label-danger' : 'label-success'" x-text="row.down"></span>
        </x-data-table.column>
        <x-data-table.column field="actions" :label="__('Actions')" :sortable="false" class="tw:whitespace-nowrap">
            <button type="button" class="btn btn-xs btn-primary" :class="isExpanded(row) && 'active'" x-on:click="toggleExpanded(row)" :disabled="row.devices < 1"
                    :title="row.devices < 1 ? @js(__('Location must have devices to show graphs')) : ''">
                <i class="fa fa-area-chart" aria-hidden="true"></i><span class="hidden-sm"> {{ __('Traffic') }}</span>
            </button>
            @can('location.update')
                <button type="button" class="btn btn-xs btn-default" x-on:click="$dispatch('location:edit', {id: row.id, location: row.location, lat: row.lat, lng: row.lng})">
                    <i class="fa fa-pencil" aria-hidden="true"></i><span class="hidden-sm"> {{ __('Edit') }}</span>
                </button>
            @endcan
            @can('location.delete')
                <button type="button" class="btn btn-xs btn-danger" :disabled="row.devices > 0 || isPending('delete' + row.id)"
                        :title="row.devices > 0 ? @js(__('Cannot delete locations used by devices')) : ''"
                        x-on:click="request('delete' + row.id, 'DELETE', route('location.destroy', {location: row.id})).then(() => { toastr.success(@js(__('Location deleted'))); reload(); }).catch(() => {})">
                    <i class="fa fa-trash" aria-hidden="true"></i><span class="hidden-sm"> {{ __('Delete') }}</span>
                </button>
            @endcan
        </x-data-table.column>
        <x-slot:expand>
            <div class="row">
                <template x-for="(graph, index) in row.graphs" :key="index">
                    <div class="col-md-3" x-html="graph"></div>
                </template>
            </div>
        </x-slot:expand>
    </x-data-table>

    <div class="modal fade" id="edit-location" tabindex="-1" role="dialog" aria-labelledby="edit-location-title">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="edit-location-title">{{ __('Edit Location') }} :: <span></span></h4>
                </div>
                <div class="modal-body">
                    <div id="location-edit-map" style="width: 568px; height: 400px;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="save-location">{{ __('Save changes') }}</button>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>
@endsection

@section('css')
    <style>
        .coordinates-field { white-space: nowrap; }
        .coordinates-field .fa { visibility: hidden; }
        .coordinates-field:hover .fa { visibility: visible; }
    </style>
@endsection

@push('scripts')
    <script src="{{ asset('js/leaflet.js') }}"></script>
    <script src="{{ asset('js/L.Control.Locate.min.js') }}"></script>
    <script>
        var locationMap = null;
        var locationMarker = null;
        var locationId = 0;

        $(document).ready(function () {
            var modal = $('#edit-location');

            // the table's edit button sends the location to edit
            window.addEventListener('location:edit', function (event) {
                var item = event.detail;
                locationId = item.id;
                $('#edit-location-title>span').text(item.location);
                modal.data('location', item).modal('show');
            });

            modal.on('shown.bs.modal', function () {
                var item = modal.data('location');
                var location = new L.LatLng(item.lat || 0, item.lng || 0);

                if (locationMap === null) {
                    config = {{ Js::from($maps_config) }}
                    locationMap = init_map('location-edit-map', config);
                    locationMarker = init_map_marker(locationMap, location);
                }

                var zoom = 17;
                if (location.lat === 0 && location.lng === 0) {
                    zoom = 1;
                }

                // move the map (which will trigger a move event and update the marker
                locationMarker.setLatLng(location);
                locationMap.setView(location, zoom);
            });

            $('#save-location').on("click", function () {
                update_location(locationId, locationMarker.getLatLng(), function (success) {
                    if (success) {
                        modal.modal('hide');
                        window.dispatchEvent(new CustomEvent('data-table:reload', {detail: {name: 'locations'}}));
                    }
                });
            });
        });
    </script>
@endpush
