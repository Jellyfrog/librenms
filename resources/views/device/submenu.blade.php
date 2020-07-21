@extends('device.index')

@section('tab')
    @isset($data['submenu'])
        <x-submenu :title="$title" :menu="$data['submenu']" :deviceid="$device_id" :currenttab="$current_tab" :selected="$vars" />
    @endisset

    @yield('tabcontent')
@endsection
