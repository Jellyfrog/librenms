<div class="panel panel-default">
    <div class="panel-heading">
        <span style="font-weight: bold;">{{ $title }}</span>

        @foreach ($menu as $m)
            @if($isSelected($m['url']))<span class="pagemenu-selected">@endif
            <a href="{{ route('device', ['device' => $deviceid, 'tab' => $currenttab, 'vars' => $m['url']]) }}">{{ $m['name'] }}</a>
            @if($isSelected($m['url']))</span>@endif

            @if(!$loop->last)
                &nbsp;|
            @endif
        @endforeach
    </div>
</div>
