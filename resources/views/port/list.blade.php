{{-- included by port.index in its panel. Rates and durations are raw numbers, formatted here --}}
@php
    $showErrors = $show_errors === 'true';
    $bps = "%1\$s ? LibreNMS.Number.formatSi(%1\$s, 2, 0, 'bps') : ''";
    $pps = "%1\$s ? LibreNMS.Number.formatSi(%1\$s, 2, 0, 'pps') : ''";
@endphp
<x-data-table :name="'ports-' . $view"
              :url="route('table.ports')"
              row-key="port_id"
              :sort="request()->boolean('errors') || request()->boolean('filter.errors.eq') ? ['errors' => 'desc'] : ['ifDescr' => 'asc']"
              :per-page-options="[25, 50, 100, 250, -1]"
              :search="false"
              filter-name="ports"
              :filter-fields="$filterFields"
              :filter="$filter"
              :hide-filter="$hideFilter"
              :panel="false">
    <x-data-table.column field="hostname" :label="__('Device')">
        <span class="alert-status tw:mr-2.5 tw:float-left" :class="row.status"></span><span x-html="row.device"></span>
    </x-data-table.column>
    <x-data-table.column field="ifDescr" :label="__('Port')" :hideable="false" x-html="row.port" />
    <x-data-table.column field="secondsIfLastChange" :label="__('Status Changed')" x-text="row.secondsIfLastChange === null ? '' : moment.duration(row.secondsIfLastChange, 'seconds').humanize()" />
    <x-data-table.column field="ifConnectorPresent" :label="__('Connected')" :visible="false" />
    <x-data-table.column field="ifSpeed" :label="__('Speed')" x-text="{{ sprintf($bps, 'row.ifSpeed') }}" />
    <x-data-table.column field="ifDuplex" :label="__('Duplex')" class="green">
        <i data-toggle="tooltip" :title="row.ifDuplex === 'halfduplex' ? @js(__('Half Duplex')) : (row.ifDuplex === 'fullduplex' ? @js(__('Full Duplex')) : @js(__('No Duplex')))"
           :class="row.ifDuplex === 'halfduplex' ? 'fa-solid fa-circle-half-stroke' : (row.ifDuplex === 'fullduplex' ? 'fa-solid fa-circle' : 'fa-regular fa-circle')"></i>
    </x-data-table.column>
    <x-data-table.column field="ifMtu" :label="__('MTU')" :visible="false" />
    <x-data-table.column field="ifInOctets_rate" :label="__('In')" class="green" x-text="{{ sprintf($bps, 'row.ifInOctets_rate') }}" />
    <x-data-table.column field="ifOutOctets_rate" :label="__('Out')" class="blue" x-text="{{ sprintf($bps, 'row.ifOutOctets_rate') }}" />
    <x-data-table.column field="ifInUcastPkts_rate" :label="__('Packets In')" :visible="$show_detail === 'true'" class="green" x-text="{{ sprintf($pps, 'row.ifInUcastPkts_rate') }}" />
    <x-data-table.column field="ifOutUcastPkts_rate" :label="__('Packets Out')" :visible="$show_detail === 'true'" class="blue" x-text="{{ sprintf($pps, 'row.ifOutUcastPkts_rate') }}" />
    <x-data-table.column field="ifInErrors_delta" :label="__('Errors In Rate')" :visible="$showErrors" class="red" />
    <x-data-table.column field="ifOutErrors_delta" :label="__('Errors Out Rate')" :visible="$showErrors" class="red" />
    <x-data-table.column field="ifInErrors" :label="__('Errors In')" :visible="$showErrors" class="red" />
    <x-data-table.column field="ifOutErrors" :label="__('Errors Out')" :visible="$showErrors" class="red" />
    <x-data-table.column field="ifType" :label="__('Media')" />
    <x-data-table.column field="ifAlias" :label="__('Description')" />
    <x-data-table.column field="actions" :label="__('Actions')" :sortable="false" html />
</x-data-table>
