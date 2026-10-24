<?php

use App\Models\Location;

$authorized_all = $auth; // guest auth (signed url, unauth graphs) covers all devices

$location = Location::with('devices')->find($vars['id']);

foreach ($location->devices ?? [] as $device) {
    if ($authorized_all || device_permitted($device->device_id)) {
        $devices[] = $device->toArray();
        $title = $location->location;
        $auth = true;
    }
}
