<?php
/*
 * LibreNMS module to Display data from F5 BigIP LTM Devices
 *
 * Copyright (c) 2019 Yacine BENAMSILI <https://github.com/yac01/ yacine.benamsili@homail.com>
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
$url = \LibreNMS\Util\Url::generate($vars, ['type' => 'ltm_bwc', 'subtype' => 'ltm_bwc_det']);
foreach ($components as $id => $array) {
    if ($array['type'] != 'f5-ltm-bwc') {
        continue;
    }
    $rows[] = [
        'id' => $id,
        'name' => $array['label'],
        'url' => $url . 'bwcid=' . $id,
        'failed' => false,
    ];
}
echo view('device.loadbalancer.list', ['name' => 'ltm-bwc', 'rows' => $rows, 'columns' => [], 'status' => false]);
?>

<div class="panel panel-default" id="BitsDropped">
    <div class="panel-heading">
        <h3 class="panel-title">Traffic Dropped</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = [];
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width'] = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to'] = \App\Facades\LibrenmsConfig::get('time.now');
        $graph_array['type'] = 'device_bigip_ltm_allbwc_BitsDropped';
        require 'includes/html/print-graphrow.inc.php';
        ?>
    </div>
</div>
<div class="panel panel-default" id="Bitsin">
    <div class="panel-heading">
        <h3 class="panel-title">Traffic In</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = [];
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width'] = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to'] = \App\Facades\LibrenmsConfig::get('time.now');
        $graph_array['type'] = 'device_bigip_ltm_allbwc_Bitsin';
        require 'includes/html/print-graphrow.inc.php';
        ?>
    </div>
</div>


<div class="panel panel-default" id="pktsin">
    <div class="panel-heading">
        <h3 class="panel-title">Packets In</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = [];
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width'] = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to'] = \App\Facades\LibrenmsConfig::get('time.now');
        $graph_array['type'] = 'device_bigip_ltm_allbwc_pktsin';
        require 'includes/html/print-graphrow.inc.php';
        ?>
    </div>
</div>

