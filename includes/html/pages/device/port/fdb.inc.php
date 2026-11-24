<?php

$no_refresh = true;

echo Blade::render('<x-fdb-table :port-id="$portId" />', ['portId' => $port['port_id']]);
