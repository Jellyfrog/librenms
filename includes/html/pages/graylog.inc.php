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
$pagetitle[] = 'Graylog';

// old links put stream, device, range and loglevel in the url
$filter = [];
foreach (['stream' => 'stream', 'device' => 'device_id', 'range' => 'range', 'loglevel' => 'loglevel'] as $var => $field) {
    if (isset($vars[$var]) && $vars[$var] !== '') {
        $filter[$field] = ['eq' => $vars[$var]];
    }
}

echo Blade::render('<x-graylog-table :filter="$filter" />', ['filter' => $filter]);
