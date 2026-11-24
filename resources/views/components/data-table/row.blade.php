{{-- one row of x-data-table, included by its table partial, uses its variables --}}
<tr @class([
        'tw:align-middle tw:*:px-3 tw:*:py-1.5 tw:max-md:relative tw:max-md:grid tw:max-md:grid-cols-2 tw:max-md:gap-3 tw:max-md:px-4 tw:max-md:py-3 tw:max-md:*:p-0',
        'tw:max-md:pl-11' => $selectable,
        'tw:max-md:[&>[data-label]]:before:block tw:max-md:[&>[data-label]]:before:mb-1 tw:max-md:[&>[data-label]]:before:content-[attr(data-label)] tw:max-md:[&>[data-label]]:before:text-gray-500',
    ])
    :class="isSelected(row) ? 'tw:bg-blue-50/60 tw:dark:bg-blue-950/30' : 'tw:hover:bg-gray-50 tw:dark:hover:bg-dark-gray-400'">
    @if ($selectable)
        {{-- below md the checkbox is pinned to the top left corner of the card --}}
        <td class="tw:max-md:absolute tw:max-md:left-4 tw:max-md:top-3.5">
            <input type="checkbox"
                   class="tw:m-0"
                   :checked="isSelected(row)"
                   x-on:change="toggleSelected(row, $event.target.checked)"
                   :aria-label="@js(__('Select') . ' ') + {{ $rowLabel }}">
        </td>
    @endif
    {{ $slot }}
</tr>
