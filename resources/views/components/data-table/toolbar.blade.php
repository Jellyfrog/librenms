{{-- included by x-data-table, uses its variables --}}
<div class="tw:flex tw:flex-wrap tw:items-center tw:gap-2">
    @if ($title)
        <h3 class="panel-title tw:mr-2">{{ $title }}</h3>
    @endif
    @if ($filterName)
        <x-filter :name="$filterName" :fields="$filterFields" :initial="$filter" :hide="$hideFilter" />
    @endif
    @isset($heading)
        <div {{ $heading->attributes->class(['tw:flex tw:flex-wrap tw:items-center tw:gap-2']) }}>{{ $heading }}</div>
    @endisset
    <div class="tw:flex tw:items-center tw:gap-2 tw:ml-auto">
        @if ($search)
            <div class="tw:relative">
                <i class="fa fa-search tw:absolute tw:left-2.5 tw:top-1/2 tw:-translate-y-1/2 tw:text-gray-400 tw:pointer-events-none" aria-hidden="true"></i>
                <input type="search" x-model.debounce.300ms="state.globalFilter" class="lnms-input tw:pl-8 tw:w-48" placeholder="{{ __('Search') }}" aria-label="{{ __('Search') }}">
            </div>
        @endif
        <button type="button" class="lnms-btn lnms-btn-default" x-on:click="reload()" :disabled="loading" title="{{ __('Refresh') }}" aria-label="{{ __('Refresh') }}">
            <i class="fa fa-refresh" :class="loading && 'fa-spin'" aria-hidden="true"></i>
        </button>
        @if ($export)
            <div class="tw:relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
                <button type="button" class="lnms-btn lnms-btn-default" x-on:click="open = ! open" :aria-expanded="open" title="{{ __('Export') }}" aria-label="{{ __('Export') }}">
                    <i class="fa fa-download" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-cloak x-transition
                     class="tw:absolute tw:right-0 tw:top-full tw:mt-1 tw:z-50 tw:min-w-48 tw:py-1 tw:rounded-lg tw:border tw:border-gray-200 tw:dark:border-dark-gray-200 tw:bg-white tw:dark:bg-dark-gray-400 tw:shadow-lg">
                    @foreach ([true => __('Export page'), false => __('Export all results')] as $page => $label)
                        <button type="button" class="tw:flex tw:items-center tw:gap-2 tw:w-full tw:px-3 tw:py-1.5 tw:border-0 tw:bg-transparent tw:text-left tw:whitespace-nowrap tw:hover:bg-gray-50 tw:dark:hover:bg-dark-gray-300"
                                x-on:click="exportCsv(@js((bool) $page)); open = false">
                            <i class="fa-solid fa-fw fa-file-csv" aria-hidden="true"></i> {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($columnToggle)
            <div class="tw:relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
                <button type="button" class="lnms-btn lnms-btn-default" x-on:click="open = ! open" :aria-expanded="open" title="{{ __('Columns') }}" aria-label="{{ __('Columns') }}">
                    <i class="fa fa-table-columns" aria-hidden="true"></i>
                </button>
                <div x-show="open" x-cloak x-transition
                     class="tw:absolute tw:right-0 tw:top-full tw:mt-1 tw:z-50 tw:min-w-48 tw:py-1 tw:rounded-lg tw:border tw:border-gray-200 tw:dark:border-dark-gray-200 tw:bg-white tw:dark:bg-dark-gray-400 tw:shadow-lg">
                    <template x-for="column in columns.filter((column) => column.hideable)" :key="column.id">
                        <label class="tw:flex tw:items-center tw:gap-2 tw:m-0 tw:px-3 tw:py-1.5 tw:font-normal tw:whitespace-nowrap tw:cursor-pointer tw:hover:bg-gray-50 tw:dark:hover:bg-dark-gray-300">
                            <input type="checkbox" class="tw:m-0" :checked="isVisible(column.id)" x-on:change="toggleColumn(column.id)">
                            <span x-text="column.label || column.id"></span>
                        </label>
                    </template>
                </div>
            </div>
        @endif
    </div>
</div>
