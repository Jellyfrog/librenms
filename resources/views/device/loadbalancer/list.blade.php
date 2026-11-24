{{--
    F5 load balancer component list, sorted and searched in the browser.
    rows: id, name, url (detail page, optional), failed, message and the extra columns
    columns: extra columns, field => label, shown between the name and the status
--}}
<x-data-table :name="'f5-' . $name" mode="local" :rows="$rows" :sort="['name' => 'asc']" :panel="false" row-class="row.failed && 'danger'">
    <x-data-table.column field="name" :label="__('Name')" :hideable="false">
        <template x-if="row.url"><a :href="row.url" x-text="row.name"></a></template>
        <template x-if="! row.url"><span x-text="row.name"></span></template>
    </x-data-table.column>
    @foreach($columns as $field => $label)
        <x-data-table.column :field="$field" :label="$label" />
    @endforeach
    @if($status ?? true)
        <x-data-table.column field="message" :label="__('Status')" />
    @endif
</x-data-table>
