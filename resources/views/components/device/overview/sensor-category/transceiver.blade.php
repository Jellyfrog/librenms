@props(['device', 'panel'])

@php
    // show sensors under the transceiver they measure, the rest keep their group
    $transceivers = $device->transceivers()->with('port.device')->whereNotNull('entity_physical_index')->get()->keyBy('entity_physical_index');
    [$measured, $other] = collect($panel['groups'])->flatten(1)
        ->partition(fn ($data) => $transceivers->has($data['sensor']->entPhysicalIndex));
@endphp

<x-device.overview.panel :title="$panel['title']" :icon="$panel['icon']"
    :href="route('device', ['device' => $device->device_id, 'tab' => 'ports', 'vars' => 'transceivers'])">
    <div class="tw:divide-y tw:divide-gray-300 tw:dark:divide-zinc-800">
        @foreach($measured->groupBy('sensor.entPhysicalIndex') as $index => $sensors)
            <x-device.overview.sensor-group :device="$device" :sensors="$sensors">
                @if($transceivers[$index]->port)<x-port-link :port="$transceivers[$index]->port" :vars="['view' => 'transceiver']" />@endif
                <x-icons.transceiver /> {{ $transceivers[$index]->vendor }} {{ $transceivers[$index]->type }}
            </x-device.overview.sensor-group>
        @endforeach
        @foreach($other->groupBy('sensor.group') as $group => $sensors)
            <x-device.overview.sensor-group :device="$device" :sensors="$sensors">{{ $group }}</x-device.overview.sensor-group>
        @endforeach
    </div>
</x-device.overview.panel>
