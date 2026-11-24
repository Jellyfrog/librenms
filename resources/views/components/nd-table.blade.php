@props([
    'entries',
    'showPort' => true,
])

{{-- IPv6 neighbours (Ipv6Nd models), sorted and searched in the browser --}}
<x-data-table :name="$showPort ? 'nd-device' : 'nd-port'"
              mode="local"
              :rows="\App\Http\Resources\Table\NdResource::collection($entries)->resolve()"
              :sort="['ipv6_address' => 'asc']"
              {{ $attributes }}>
    @if($showPort)
        <x-data-table.column field="interface" :label="__('Port')" html />
    @endif
    <x-data-table.column field="mac_address" :label="__('MAC address')" ::title="row.mac_oui ? row.mac_address + ' (' + row.mac_oui + ')' : row.mac_address" />
    <x-data-table.column field="mac_oui" :label="__('Vendor')" :visible="false" />
    <x-data-table.column field="ipv6_address" :label="__('IPv6 address')" />
    <x-data-table.column field="remote_device" :label="__('Remote device')" :searchable="false" html />
    <x-data-table.column field="remote_interface" :label="__('Remote interface')" :searchable="false" html />
</x-data-table>
