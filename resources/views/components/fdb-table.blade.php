@props([
    'deviceId' => null,
    'portId' => null,
])

{{-- Forwarding database of a device or a port. The search box matches MAC, IP, VLAN and port description --}}
<x-data-table :name="$portId ? 'fdb-port' : 'fdb-device'"
              :url="route('table.fdb')"
              :params="array_filter(['device_id' => $deviceId, 'port_id' => $portId])"
              :sort="['mac_address' => 'asc']"
              send-columns
              {{ $attributes }}>
    <x-data-table.column field="mac_address" :label="__('MAC Address')" width="150px" ::title="row.mac_oui ? row.mac_address + ' (' + row.mac_oui + ')' : row.mac_address" />
    <x-data-table.column field="mac_oui" :label="__('Vendor')" :sortable="false" width="150px" :visible="(bool) \App\Facades\LibrenmsConfig::get('mac_oui.enabled')" />
    <x-data-table.column field="ipv4_address" :label="__('IPv4 Address')" :sortable="false" />
    <x-data-table.column field="interface" :label="__('Port')" html />
    @unless($portId)
        <x-data-table.column field="description" :label="__('Description')" />
    @endunless
    <x-data-table.column field="vlan" :label="__('Vlan')" width="60px" />
    {{-- names are looked up while the column is shown --}}
    <x-data-table.column field="dnsname" :label="__('DNS Name')" :sortable="false" :visible="false" />
    <x-data-table.column field="first_seen" :label="__('First seen')" width="165px" />
    <x-data-table.column field="last_seen" :label="__('Last seen')" width="165px" />
</x-data-table>
