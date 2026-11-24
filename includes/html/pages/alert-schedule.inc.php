<?php

/*
 * LibreNMS
 *
 * Copyright (c) 2014 Neil Lathwood <https://github.com/laf/ http://www.lathwood.co.uk/fa>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

use App\Models\AlertSchedule;

$pagetitle[] = 'Alert Schedule';
$no_refresh = true;
if (Gate::allows('viewAny', AlertSchedule::class)) {
    include 'includes/html/modal/alert_schedule.inc.php';
    include 'includes/html/modal/remove_alert_schedule.inc.php'; ?>

<div class="row">
    <div class="col-sm-12">
        <span id="message"></span>
    </div>
</div>

<?php echo view('alerts.schedule-table'); ?>

    <?php
}//end if
