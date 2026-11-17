{{-- included by x-data-table, uses its variables --}}
<div class="tw:text-gray-500 tw:dark:text-dark-white-400" role="status" aria-live="polite"
     x-text="total > 0 ? @js(__('Showing :first–:last of :total', ['first' => '__first__', 'last' => '__last__', 'total' => '__total__'])).replace('__first__', firstItem).replace('__last__', lastItem).replace('__total__', total) : ''"></div>
<div class="tw:flex tw:items-center tw:gap-2 tw:ml-auto">
    <select x-model.number="perPage" class="lnms-input tw:w-auto" aria-label="{{ __('Rows per page') }}">
        @foreach ($perPageOptions as $count)
            <option value="{{ $count }}">{{ $count < 0 ? __('All') : $count }}</option>
        @endforeach
    </select>
    <button type="button" class="lnms-btn lnms-btn-default tw:disabled:opacity-50" x-on:click="goTo(1)" :disabled="page <= 1" aria-label="{{ __('First page') }}"><i class="fa fa-angles-left" aria-hidden="true"></i></button>
    <button type="button" class="lnms-btn lnms-btn-default tw:disabled:opacity-50" x-on:click="goTo(page - 1)" :disabled="page <= 1" aria-label="{{ __('Previous page') }}"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
    <span class="tw:whitespace-nowrap" x-text="page + ' / ' + pageCount"></span>
    <button type="button" class="lnms-btn lnms-btn-default tw:disabled:opacity-50" x-on:click="goTo(page + 1)" :disabled="page >= pageCount" aria-label="{{ __('Next page') }}"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>
    <button type="button" class="lnms-btn lnms-btn-default tw:disabled:opacity-50" x-on:click="goTo(pageCount)" :disabled="page >= pageCount" aria-label="{{ __('Last page') }}"><i class="fa fa-angles-right" aria-hidden="true"></i></button>
</div>
