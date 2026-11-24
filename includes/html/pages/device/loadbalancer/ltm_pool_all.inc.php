<?php
/*
 * LibreNMS module to Display data from F5 BigIP LTM Devices
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

?>
<?php
$rows = [];
$url = \LibreNMS\Util\Url::generate($vars, ['type' => 'ltm_pool', 'subtype' => 'ltm_pool_details']);
foreach ($components as $id => $array) {
    if ($array['type'] != 'f5-ltm-pool') {
        continue;
    }
    $rows[] = [
        'id' => $id,
        'name' => $array['label'],
        'url' => $url . 'poolid=' . $id,
        'minup' => $array['minup'],
        'currentup' => $array['currentup'],
        'failed' => $array['status'] != 0,
        'message' => $array['status'] != 0 ? $array['error'] : 'Ok',
    ];
}
echo view('device.loadbalancer.list', ['name' => 'ltm-pool', 'rows' => $rows, 'columns' => ['minup' => __('Minimum Members'), 'currentup' => __('Current Members')]]);
?>

