@props(['device', 'panels'])

@foreach($panels as $panel)
    {{-- a category can present its panel with its own view in sensor-category/<category>.blade.php --}}
    @if($panel['category'] && view()->exists('components.device.overview.sensor-category.' . $panel['category']))
        <x-dynamic-component :component="'device.overview.sensor-category.' . $panel['category']" :device="$device" :panel="$panel" />
    @else
        <x-device.overview.panel :title="$panel['title']" :icon="$panel['icon']" :href="$panel['href']">
            <div class="tw:divide-y tw:divide-gray-300 tw:dark:divide-zinc-800">
                @foreach($panel['groups'] as $group => $sensors)
                    <x-device.overview.sensor-group :device="$device" :sensors="$sensors">{{ $group }}</x-device.overview.sensor-group>
                @endforeach
            </div>
        </x-device.overview.panel>
    @endif
@endforeach
