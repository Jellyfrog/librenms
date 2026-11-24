<?php

$no_refresh = true;

echo Blade::render('<x-nd-table :entries="$entries" :show-port="false" />', ['entries' => $port->nd]);
