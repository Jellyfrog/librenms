@props([
    'field',
    'label' => '',
    'html' => false,
    'sortable' => true,
    'searchable' => true,
    'hideable' => true,
    'visible' => true,
    'width' => null,
    'headerClass' => '',
])

{{--
    One column of an x-data-table, rendered once per row. The current row is "row".
    The slot is the cell content, it defaults to the escaped field value, or the raw value with html (for html the server built).
    Attributes go on the td. Hidden columns are not rendered, so their images are not loaded.

    field: column id, the sort key sent to the server and the row value used in local mode
    sortable/searchable: searchable only applies to local mode, the server decides what it searches
    hideable: listed in the column menu
    visible: initial visibility, the user's choice is remembered per table name
--}}
<template x-if="isVisible(@js($field))"
          data-column="{{ $field }}"
          data-label="{{ $label }}"
          data-sortable="{{ $sortable ? 1 : 0 }}"
          data-searchable="{{ $searchable ? 1 : 0 }}"
          data-hideable="{{ $hideable ? 1 : 0 }}"
          data-visible="{{ $visible ? 1 : 0 }}"
          @if ($width) data-width="{{ $width }}" @endif
          @if ($headerClass) data-header-class="{{ $headerClass }}" @endif>
    <td {{ $attributes }} data-label="{{ $label }}">
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @elseif ($html)
            <span x-html="row[@js($field)]"></span>
        @elseif (! $attributes->hasAny(['x-html', 'x-text']))
            <span x-text="row[@js($field)]"></span>
        @endif
    </td>
</template>
