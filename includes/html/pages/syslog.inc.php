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

use Illuminate\Support\Facades\Gate;

$no_refresh = true;

if (isset($vars['action']) && $vars['action'] == 'expunge' && Gate::allows('syslog.delete')) {
    \App\Models\Syslog::truncate();
    print_message('syslog truncated');
}

$pagetitle[] = 'Syslog';

// old links put device, program, priority, from and to in the url
$filter = \App\Models\Syslog::filtersFromRequest(request()->duplicate([...request()->query(), ...$vars]), [
    'device' => 'device_id',
    'program' => 'program',
    'priority' => 'priority',
    'from' => ['timestamp', 'after'],
    'to' => ['timestamp', 'before'],
]);

echo Blade::render('<x-syslog-table :filter="$filter" />', ['filter' => $filter]);
