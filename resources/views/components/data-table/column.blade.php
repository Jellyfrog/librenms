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
    'headerTitle' => null,
])

{{--
    One column of an x-data-table, rendered once per row. The current row is "row".
    The cell shows the escaped field value, or the slot when given. Attributes go on the td.

    field: column id, the sort key sent to the server and the row value used in local mode
    html: the field is html the server built (rendered with x-html), keep everything else text
    sortable/searchable: searchable only applies to local mode, the server decides what it searches
    hideable: listed in the column menu
    visible: initial visibility, the user's choice is remembered per table name
    width/headerClass/headerTitle: header cell width, classes and tooltip

    Hidden columns are not rendered at all (x-if), so their bindings and images cost nothing.
--}}
<template x-if="isVisible(@js($field))"
          data-column="{{ $field }}"
          data-label="{{ $label }}"
          data-sortable="{{ $sortable ? 1 : 0 }}"
          data-searchable="{{ $searchable ? 1 : 0 }}"
          data-hideable="{{ $hideable ? 1 : 0 }}"
          data-visible="{{ $visible ? 1 : 0 }}"
          @if ($width) data-width="{{ $width }}" @endif
          @if ($headerClass) data-header-class="{{ $headerClass }}" @endif
          @if ($headerTitle) data-header-title="{{ $headerTitle }}" @endif>
    @if ($slot->isNotEmpty() || $attributes->hasAny(['x-html', 'x-text']))
        <td {{ $attributes }} data-label="{{ $label }}">{{ $slot }}</td>
    @elseif ($html)
        <td {{ $attributes }} data-label="{{ $label }}" x-html="row[@js($field)]"></td>
    @else
        <td {{ $attributes }} data-label="{{ $label }}" x-text="row[@js($field)]"></td>
    @endif
</template>
