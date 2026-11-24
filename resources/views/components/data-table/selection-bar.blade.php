{{-- included by x-data-table when it has an actions slot, uses its variables. Text uses __count__ as the count placeholder --}}
<div x-show="selectionCount > 0" x-cloak
     class="tw:flex tw:flex-wrap tw:items-center tw:gap-2 tw:px-4 tw:py-2 tw:border-b tw:border-blue-200 tw:dark:border-blue-900 tw:bg-blue-50 tw:dark:bg-blue-950/40">
    <span class="tw:font-semibold tw:mr-2" x-text="@js(__('components.data-table.selected', ['count' => '__count__'])).replace('__count__', selectionCount)"></span>
    {{ $actions }}
    <button type="button" class="tw:ml-auto tw:p-0 tw:border-0 tw:bg-transparent tw:text-blue-600 tw:hover:underline tw:dark:text-blue-400" x-on:click="clearSelection()">{{ __('Clear selection') }}</button>
</div>

{{-- offer to extend a full page selection to every row matching the filter --}}
<div x-show="pageSelected && total > rows.length" x-cloak
     class="tw:px-4 tw:py-2 tw:text-center tw:border-b tw:border-gray-200 tw:dark:border-dark-gray-200 tw:bg-gray-50 tw:dark:bg-dark-gray-400">
    <span x-text="(allMatching ? @js($allMatchingSelected) : @js($pageSelected)).replace('__count__', allMatching ? total : rows.length)"></span>
    <button type="button"
            class="tw:p-0 tw:border-0 tw:bg-transparent tw:text-blue-600 tw:hover:underline tw:dark:text-blue-400"
            x-on:click="allMatching ? clearSelection() : selectAllMatching()"
            x-text="allMatching ? @js(__('Clear selection')) : @js($selectAllMatching).replace('__count__', total)"></button>
</div>

<div role="status" aria-live="polite">
    <div x-show="notice && selectionCount === 0" x-cloak x-transition.opacity.duration.300ms
         class="tw:flex tw:items-center tw:gap-2 tw:px-4 tw:py-2 tw:border-b tw:border-green-200 tw:dark:border-green-900 tw:bg-green-50 tw:dark:bg-green-950/40 tw:text-green-800 tw:dark:text-green-300">
        <i class="fa fa-circle-check" aria-hidden="true"></i>
        <span x-text="notice"></span>
        <button type="button" class="tw:ml-auto tw:p-0 tw:border-0 tw:bg-transparent tw:text-green-800 tw:dark:text-green-300 tw:opacity-60 tw:hover:opacity-100" x-on:click="notice = null" aria-label="{{ __('Close') }}">
            <i class="fa fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
</div>
