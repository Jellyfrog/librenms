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

echo Blade::render('<x-eventlog-table :filter="$filter" />', ['filter' => \App\Models\Eventlog::filtersFromRequest(request(), ['device' => 'device_id', 'eventtype' => 'type'])]);
