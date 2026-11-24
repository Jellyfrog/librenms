<?php

$no_refresh = true;

echo Blade::render('<x-nac-table :port-id="$portId" />', ['portId' => $port['port_id']]);
