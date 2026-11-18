<?php

/*
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 *
 * @package    LibreNMS
 * @subpackage webui
 * @link       https://www.librenms.org
 * @copyright  2017 LibreNMS
 * @author     LibreNMS Contributors
*/

$no_refresh = true;
$pagetitle[] = 'Eventlog';

$filter = Request::array('filter');
// old links use ?device= and ?eventtype=
if ($device_id = (int) Request::input('device')) {
    $filter['device_id'] ??= ['eq' => $device_id];
}
if ($type = Request::input('eventtype')) {
    $filter['type'] ??= ['eq' => $type];
}

echo Blade::render('<x-eventlog-table :filter="$filter" />', ['filter' => $filter]);
