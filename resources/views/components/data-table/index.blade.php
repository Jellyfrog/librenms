@props([
    'url' => null,
    'name' => null,
    'rows' => null,
    'params' => [],
    'sort' => [],
    'perPage' => 50,
    'perPageOptions' => [50, 100, 250, -1],
    'search' => true,
    'rowKey' => 'id',
    'filterName' => null,
    'filterFields' => [],
    'filter' => [],
    'hideFilter' => false,
    'columnToggle' => true,
    'export' => null,
    'panel' => true,
    'title' => null,
])

{{--
    Sortable, searchable, filterable and paginated table. Replaces jquery-bootgrid.
    The JS lives in resources/js/components/alpine/dataTable.js (Alpine.data('dataTable')), built on @tanstack/alpine-table.

    url: endpoint, a TableController route. The server sorts, searches, filters and paginates
    name: identifies the table for remembered column/page size choices
    rows: all rows, instead of url. The browser sorts, searches and paginates them, x-filter filters need a url
    params: extra request parameters, sent with every request
    sort: initial sort, column => asc|desc in priority order. Shift+click a header to sort on more columns
    perPage/perPageOptions: page size choices, -1 shows all rows
    search: show the search box
    filterName/filterFields/filter/hideFilter: show an x-filter, its filters are sent as filter[field][op]=value
    columnToggle: show the column visibility menu
    export: CSV export url (a TableController export route), adds a button to export the page or every result
    panel: wrap the table in an x-panel, set false when the table is already inside one

    Slots, the current row is "row":
    default  x-data-table.column cells, one per column
    heading  extra controls in the toolbar
    empty    text when no rows match
--}}
@php
    $config = [
        'url' => $url,
        'name' => $name,
        'local' => $rows !== null,
        'params' => (object) $params,
        'sort' => (object) $sort,
        'perPage' => $perPage,
        'rowKey' => $rowKey,
        'filter' => (object) $filter,
        'lang' => ['request_failed' => __('Request failed')],
        'exportUrl' => $export,
    ];
    $toolbar = $filterName || $search || $columnToggle || $export || isset($heading);
@endphp
<div {{ $attributes }}
     x-data="dataTable(@js($config))"
     @if ($filterName) x-on:filter:apply.window="if ($event.detail.name === @js($filterName)) applyFilter($event.detail.filters)" @endif>
    @if ($panel)
        <x-panel class="tw:mb-0" :title="$title">
            @if ($toolbar)
                <x-slot name="heading">
                    @include('components.data-table.toolbar')
                </x-slot>
            @endif
            <x-slot name="table">
                @include('components.data-table.table')
            </x-slot>
            <x-slot name="footer" class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-3">
                @include('components.data-table.pagination')
            </x-slot>
        </x-panel>
    @else
        @if ($toolbar)
            <div class="tw:pb-3">
                @include('components.data-table.toolbar')
            </div>
        @endif
        @include('components.data-table.table')
        <div class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-3 tw:pt-3">
            @include('components.data-table.pagination')
        </div>
    @endif
    @if ($rows !== null)
        {{-- read once by the JS, kept out of x-data so Alpine does not compile it --}}
        <script type="application/json" data-table-rows>@json($rows)</script>
    @endif
</div>
