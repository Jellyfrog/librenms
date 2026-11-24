{{-- one row per customer, the port columns list each port of the customer on its own line --}}
<x-data-table name="customers"
              :url="route('table.customers')"
              :sort="['port_descr_descr' => 'asc']"
              :title="__('Customers')">
    <x-data-table.column field="port_descr_descr" :label="__('Customer')" :hideable="false" class="tw:font-bold" />
    @foreach(['hostname' => __('Device'), 'ifDescr' => __('Interface')] as $field => $label)
        <x-data-table.column :field="$field" :label="$label" :sortable="false">
            <template x-for="port in row.ports" :key="port.id"><div x-html="port.{{ $field }}"></div></template>
        </x-data-table.column>
    @endforeach
    @foreach(['speed' => __('Speed'), 'circuit' => __('Circuit'), 'notes' => __('Notes')] as $field => $label)
        <x-data-table.column :field="$field" :label="$label" :sortable="false">
            <template x-for="port in row.ports" :key="port.id"><div x-text="port.{{ $field }} || '\u00a0'"></div></template>
        </x-data-table.column>
    @endforeach
    <x-data-table.column field="graphs" :label="__('Traffic')" :sortable="false">
        <div class="tw:flex tw:flex-wrap tw:gap-1">
            <template x-for="(graph, index) in row.graphs" :key="index"><div x-html="graph"></div></template>
        </div>
    </x-data-table.column>
</x-data-table>
