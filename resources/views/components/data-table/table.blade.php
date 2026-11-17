{{-- included by x-data-table, uses its variables. Below md rows are cards, cells show their data-label --}}
<div class="tw:overflow-x-auto tw:transition-opacity" :class="loading && 'tw:opacity-60'" :aria-busy="loading">
    <table class="tw:w-full tw:max-md:block">
        <thead class="tw:max-md:hidden">
        <tr class="tw:border-b tw:border-gray-200 tw:dark:border-dark-gray-200 tw:text-left tw:text-gray-500 tw:dark:text-dark-white-400">
            <template x-for="column in columns" :key="column.id">
                <th x-show="isVisible(column.id)"
                    class="tw:px-3 tw:py-2 tw:font-semibold tw:whitespace-nowrap"
                    :class="column.headerClass"
                    :style="column.width && { width: column.width }"
                    :aria-sort="sortDirection(column.id) ? (sortDirection(column.id) === 'asc' ? 'ascending' : 'descending') : null">
                    <template x-if="column.sortable">
                        <button type="button"
                                class="tw:inline-flex tw:items-center tw:gap-1 tw:p-0 tw:border-0 tw:bg-transparent tw:font-semibold tw:text-inherit tw:hover:text-gray-900 tw:dark:hover:text-dark-white-100"
                                x-on:click="sortBy(column.id, $event)"
                                title="{{ __('Click to sort, shift+click to sort on more columns') }}">
                            <span x-text="column.label"></span>
                            <i class="fa fa-fw" aria-hidden="true"
                               :class="{ 'fa-sort tw:opacity-30': ! sortDirection(column.id), 'fa-sort-up': sortDirection(column.id) === 'asc', 'fa-sort-down': sortDirection(column.id) === 'desc' }"></i>
                            <sup x-show="sortPosition(column.id)" x-text="sortPosition(column.id)" class="tw:-ml-1"></sup>
                        </button>
                    </template>
                    <template x-if="! column.sortable">
                        <span x-text="column.label"></span>
                    </template>
                </th>
            </template>
        </tr>
        </thead>
        <tbody class="tw:max-md:block tw:divide-y tw:divide-gray-100 tw:dark:divide-dark-gray-300">
        <template data-table-row x-for="(row, index) in rows" :key="rowId(row, index)">
            <tr class="tw:align-middle tw:*:px-3 tw:*:py-1.5 tw:hover:bg-gray-50 tw:dark:hover:bg-dark-gray-400 tw:max-md:grid tw:max-md:grid-cols-2 tw:max-md:gap-3 tw:max-md:px-4 tw:max-md:py-3 tw:max-md:*:p-0 tw:max-md:[&>[data-label]]:before:block tw:max-md:[&>[data-label]]:before:mb-1 tw:max-md:[&>[data-label]]:before:content-[attr(data-label)] tw:max-md:[&>[data-label]]:before:text-gray-500">
                {{ $slot }}
            </tr>
        </template>
        <tr x-show="error" x-cloak class="tw:max-md:block">
            <td :colspan="visibleColumnCount" class="tw:max-md:block tw:px-4 tw:py-8 tw:text-center tw:text-red-600 tw:dark:text-red-400">
                <i class="fa fa-triangle-exclamation" aria-hidden="true"></i> <span x-text="error"></span>
            </td>
        </tr>
        <tr x-show="! loading && ! error && rows.length === 0" x-cloak class="tw:max-md:block">
            <td :colspan="visibleColumnCount" class="tw:max-md:block tw:px-4 tw:py-8 tw:text-center tw:text-gray-500 tw:dark:text-dark-white-400">{{ $empty ?? __('No results found') }}</td>
        </tr>
        </tbody>
    </table>
</div>
