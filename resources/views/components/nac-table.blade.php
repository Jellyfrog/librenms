@props([
    'deviceId' => null,
    'portId' => null,
])

{{-- NAC sessions, of a device or port when given. The toolbar switch includes historical sessions --}}
<x-data-table :name="$portId ? 'nac-port' : ($deviceId ? 'nac-device' : 'nac')"
              :url="route('table.port-nac')"
              :params="array_filter(['device_id' => $deviceId, 'port_id' => $portId])"
              :sort="['device_id' => 'asc']"
              :per-page-options="[25, 50, 100, -1]"
              :title="__('NAC')"
              {{ $attributes }}>
    <x-slot:heading>
        <label class="tw:flex tw:items-center tw:gap-2 tw:m-0 tw:font-normal">
            <x-toggle x-on:change="config.params.historical = $event.target.checked ? 1 : 0; changed(true)" />
            {{ __('Include historical NAC entries') }}
        </label>
    </x-slot:heading>
    @unless($deviceId || $portId)
        <x-data-table.column field="device_id" :label="__('Devices')" width="140px" html />
    @endunless
    @unless($portId)
        <x-data-table.column field="port_id" :label="__('Ports')" width="100px" html />
    @endunless
    <x-data-table.column field="mac_address" :label="__('Mac Address')" width="170px" ::title="row.mac_oui ? row.mac_address + ' (' + row.mac_oui + ')' : row.mac_address" />
    <x-data-table.column field="mac_oui" :label="__('Vendor')" :sortable="false" width="130px" :visible="(bool) \App\Facades\LibrenmsConfig::get('mac_oui.enabled')" />
    <x-data-table.column field="ip_address" :label="__('IP Address')" width="140px" />
    <x-data-table.column field="vlan" :label="__('Vlan')" width="60px" />
    <x-data-table.column field="domain" :label="__('Domain')" html />
    <x-data-table.column field="host_mode" :label="__('Host Mode')" />
    <x-data-table.column field="username" :label="__('Username')" width="250px" />
    <x-data-table.column field="authz_by" :label="__('Auth By')" />
    <x-data-table.column field="timeout" :label="__('Timeout')" />
    <x-data-table.column field="time_elapsed" :label="__('Time Elapsed')" />
    <x-data-table.column field="time_left" :label="__('Time Left')" :visible="false" />
    <x-data-table.column field="authc_status" :label="__('NAC Authc')" html />
    <x-data-table.column field="authz_status" :label="__('NAC Authz')" html />
    <x-data-table.column field="method" :label="__('NAC Method')" html />
    <x-data-table.column field="created_at" :label="__('First seen')" />
    <x-data-table.column field="updated_at" :label="__('Last seen')" />
</x-data-table>
