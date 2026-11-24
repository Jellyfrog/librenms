<?php

$no_refresh = true;

echo Blade::render('<x-arp-table :port-id="$portId" />', ['portId' => $port['port_id']]);
