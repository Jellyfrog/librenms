@props([
    'field',
    'label' => '',
    'sortable' => true,
    'searchable' => true,
    'hideable' => true,
    'visible' => true,
    'width' => null,
    'headerClass' => '',
])

{{--
    One column of an x-data-table, rendered once per row. The current row is "row".
    The slot is the cell content, it defaults to the escaped field value: <span x-text="row.field">.
    Use x-html="row.field" for html the server built. Attributes go on the td.

    field: column id, the sort key sent to the server and the row value used in local mode
    sortable/searchable: searchable only applies to local mode, the server decides what it searches
    hideable: listed in the column menu
    visible: initial visibility, the user's choice is remembered per table name
--}}
<td {{ $attributes }}
    data-column="{{ $field }}"
    data-label="{{ $label }}"
    data-sortable="{{ $sortable ? 1 : 0 }}"
    data-searchable="{{ $searchable ? 1 : 0 }}"
    data-hideable="{{ $hideable ? 1 : 0 }}"
    data-visible="{{ $visible ? 1 : 0 }}"
    @if ($width) data-width="{{ $width }}" @endif
    @if ($headerClass) data-header-class="{{ $headerClass }}" @endif
    x-show="isVisible(@js($field))">
    @if ($slot->isEmpty() && ! $attributes->hasAny(['x-html', 'x-text']))
        <span x-text="row[@js($field)]"></span>
    @else
        {{ $slot }}
    @endif
</td>
