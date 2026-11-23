<x-data-table :name="'devices-' . ($detailed ? 'detail' : 'basic')"
              :url="route('table.device')"
              :params="['format' => $detailed ? 'list_detail' : 'list_basic']"
              row-key="device_id"
              :sort="['display' => 'asc']"
              :search="false"
              filter-name="devices"
              :filter-fields="$filterFields"
              :filter="$filter"
              :hide-filter="$hideFilter"
              :panel="false">
    <x-data-table.column field="status" :label="$detailed ? 'S.' : __('Status')" width="7px">
        <span :title="'Status: ' + row.status + ' : ' + row.extra.replace(/^label-/, '')"
              class="{{ $detailed ? 'alert-status' : 'alert-status-small' }}" :class="row.extra"></span>
    </x-data-table.column>
    <x-data-table.column field="device_id" :label="__('Id')" width="5px" :visible="$detailed" />
    <x-data-table.column field="maintenance" :label="$detailed ? 'M.' : __('Maintenance')" width="5px" :sortable="false" :visible="$detailed">
        <span x-show="row.maintenance" title="{{ __('Scheduled Maintenance') }}" class="glyphicon glyphicon-wrench"></span>
    </x-data-table.column>
    <x-data-table.column field="icon" :label="__('Vendor')" width="70px" :visible="$detailed">
        <span class="device-table-icon tw:dark:bg-gray-50 tw:dark:rounded-lg tw:dark:p-2" x-html="row.icon"></span>
    </x-data-table.column>
    <x-data-table.column field="display" :label="__('Device')">
        <span x-html="row.hostname"></span>
    </x-data-table.column>
    <x-data-table.column field="metrics" :label="__('Metrics')" :width="$detailed ? '100px' : '150px'" :sortable="false" :visible="$detailed" x-html="row.metrics" />
    <x-data-table.column field="hardware" :label="__('Platform')" x-html="row.hardware" />
    <x-data-table.column field="os" :label="__('device.attributes.os')" x-html="row.os" />
    <x-data-table.column field="uptime" :label="__('Up/Down Time')">
        <span x-show="row.status !== 'disabled'" :class="row.status === 'down' && 'red'" x-text="row.uptime"></span>
    </x-data-table.column>
    <x-data-table.column field="location" :label="__('device.attributes.location')" :visible="$detailed" x-html="row.location" />
    <x-data-table.column field="actions" :label="__('Actions')" :width="$detailed ? '120px' : '240px'" :sortable="false" header-class="device-table-header-actions" x-html="row.actions" />
</x-data-table>
