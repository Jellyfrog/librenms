@props([
    'url' => null,
    'name' => null,
    'mode' => 'server',
    'rows' => null,
    'params' => [],
    'sort' => [],
    'perPage' => 50,
    'perPageOptions' => [50, 100, 250, -1],
    'search' => true,
    'rowKey' => 'id',
    'rowLabel' => null,
    'rowClass' => null,
    'filterName' => null,
    'filterFields' => [],
    'filter' => [],
    'hideFilter' => false,
    'columnToggle' => true,
    'sendColumns' => false,
    'export' => false,
    'footer' => true,
    'panel' => true,
    'title' => null,
    'component' => 'dataTable',
    'config' => [],
    'lang' => [],
    'pageSelected' => __('components.data-table.page_selected', ['count' => '__count__']),
    'selectAllMatching' => __('components.data-table.select_all_matching', ['count' => '__count__']),
    'allMatchingSelected' => __('components.data-table.all_matching_selected', ['count' => '__count__']),
])

{{--
    Sortable, searchable, filterable and paginated table, the one table component for record lists.
    The JS lives in resources/js/components/alpine/dataTable.js (LibreNMS.dataTable), built on @tanstack/alpine-table.

    url: endpoint, see App\Http\Requests\DataTableRequest. Required in server mode
    name: identifies the table for data-table:reload events and remembered column/page size choices
    mode: server (the server sorts, searches, filters and paginates) or local (the browser does it on every row)
    rows: local mode rows, use the same resource as the endpoint would: RowResource::collection($models)->resolve()
    params: extra request parameters, sent with every request
    sort: initial sort, column => asc|desc in priority order. Shift+click a header to sort on more columns
    perPage/perPageOptions: page size choices, -1 shows all rows
    search: show the search box, sent as search
    rowKey/rowLabel: row id field, and an Alpine expression naming a row for screen readers (default row.{rowKey})
    rowClass: Alpine expression for extra row classes, for example row.failed && 'danger'
    filterName/filterFields/filter/hideFilter: show an x-filter, its filters are sent as filter[field][op]=value
    columnToggle: show the column visibility menu
    sendColumns: send the visible column ids (columns[]) so the endpoint can skip work for hidden columns
    export: CSV download button, true for {url}/export or the export url, see DataTableRequest::exportRows()
    footer: show the pagination
    panel: wrap the table in an x-panel, set false when the table is already inside one
    component: Alpine.data() name of a page specific table: Alpine.data('name', (config) => LibreNMS.dataTable(config, {...}))
    config/lang: extra config and translations for the component
    pageSelected/selectAllMatching/allMatchingSelected: selection texts, __count__ is the count placeholder

    Slots, the current row is "row":
    default  x-data-table.column cells, one per column
    heading  extra controls in the toolbar
    actions  bulk action buttons, rows are selectable when this is set, see selectionData()
    expand   details shown under a row after toggleExpanded(row), for example from a button in a cell
    empty    text when no rows match
    before   content above the table, it can use the table state
    after    content after the table (modals), it can use the table state

    Reload from outside: window.dispatchEvent(new CustomEvent('data-table:reload', {detail: {name: 'devices'}}))
--}}
@php
    $selectable = isset($actions);
    $expandable = isset($expand);
    $config = [
        ...$config,
        'url' => $url,
        'name' => $name,
        'mode' => $mode,
        'rows' => $rows,
        'params' => (object) $params,
        'sort' => (object) $sort,
        'perPage' => $perPage,
        'rowKey' => $rowKey,
        'filter' => (object) $filter,
        'selectable' => $selectable,
        'sendColumns' => $sendColumns,
        'exportUrl' => $export === true ? rtrim((string) $url, '/') . '/export' : ($export ?: null),
        'lang' => ['request_failed' => __('Request failed'), ...$lang],
    ];
    $rowLabel ??= 'row.' . $rowKey;
    $toolbar = $filterName || $search || $columnToggle || $export || isset($heading);
@endphp
<div {{ $attributes }}
     x-data="{{ $component }}(@js($config))"
     x-on:data-table:reload.window="if (! $event.detail?.name || $event.detail.name === config.name) reload()"
     @if ($filterName) x-on:filter:apply.window="if ($event.detail.name === @js($filterName)) applyFilter($event.detail.filters)" @endif>
    {{ $before ?? '' }}
    @if ($panel)
        <x-panel class="tw:mb-0" :title="$title">
            @if ($toolbar)
                <x-slot name="heading">
                    @include('components.data-table.toolbar')
                </x-slot>
            @endif
            <x-slot name="table">
                @if ($selectable)
                    @include('components.data-table.selection-bar')
                @endif
                @include('components.data-table.table')
            </x-slot>
            @if ($footer)
                <x-slot name="footer" class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-3">
                    @include('components.data-table.pagination')
                </x-slot>
            @endif
        </x-panel>
    @else
        @if ($toolbar)
            <div class="tw:pb-3">
                @include('components.data-table.toolbar')
            </div>
        @endif
        @if ($selectable)
            @include('components.data-table.selection-bar')
        @endif
        @include('components.data-table.table')
        @if ($footer)
            <div class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-3 tw:pt-3">
                @include('components.data-table.pagination')
            </div>
        @endif
    @endif
    {{ $after ?? '' }}
</div>
