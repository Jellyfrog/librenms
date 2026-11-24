@props([
    'deviceId' => null,
    'portId' => null,
])

{{-- ARP entries of a device or a port, searchable by IP or MAC address --}}
<x-data-table :name="$portId ? 'arp-port' : 'arp-device'"
              :url="route('table.arp')"
              :params="array_filter(['device_id' => $deviceId, 'port_id' => $portId])"
              :sort="['ipv4_address' => 'asc']"
              {{ $attributes }}>
    @unless($portId)
        <x-data-table.column field="interface" :label="__('Port')" html />
    @endunless
    <x-data-table.column field="mac_address" :label="__('MAC address')" ::title="row.mac_oui ? row.mac_address + ' (' + row.mac_oui + ')' : row.mac_address" />
    @config('mac_oui.enabled')
        <x-data-table.column field="mac_oui" :label="__('Vendor')" :sortable="false" :visible="false" />
    @endconfig
    <x-data-table.column field="ipv4_address" :label="__('IPv4 address')" />
    <x-data-table.column field="remote_device" :label="__('Remote device')" :sortable="false" html />
    <x-data-table.column field="remote_interface" :label="__('Remote interface')" :sortable="false" html />
</x-data-table>
