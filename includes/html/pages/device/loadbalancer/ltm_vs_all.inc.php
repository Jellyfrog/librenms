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
$url = \LibreNMS\Util\Url::generate($vars, ['type' => 'ltm_vs', 'subtype' => 'ltm_vs_det']);
// pool label => component id, to link the virtual server's default pool
$pools = collect($components)->filter(fn ($component) => $component['type'] == 'f5-ltm-pool')->map(fn ($component) => $component['label'])->flip();
foreach ($components as $id => $array) {
    if ($array['type'] != 'f5-ltm-vs') {
        continue;
    }
    $rows[] = [
        'id' => $id,
        'name' => $array['label'],
        'url' => $url . 'vsid=' . $id . '/poolid=' . ($pools[$array['pool']] ?? 0),
        'host' => $array['IP'] . ':' . $array['port'],
        'pool' => $array['pool'],
        'failed' => $array['status'] != 0,
        'message' => $array['status'] != 0 ? $array['error'] : 'Ok',
    ];
}
echo view('device.loadbalancer.list', ['name' => 'ltm-vs', 'rows' => $rows, 'columns' => ['host' => __('IP : Port'), 'pool' => __('Default Pool')]]);
?>

<div class="panel panel-default" id="currconnections">
    <div class="panel-heading">
        <h3 class="panel-title">Current Connections</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = [];
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width'] = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to'] = \App\Facades\LibrenmsConfig::get('time.now');
        $graph_array['type'] = 'device_bigip_ltm_allvs_currconns';
        require 'includes/html/print-graphrow.inc.php';
        ?>
    </div>
</div>
<div class="panel panel-default" id="connections">
    <div class="panel-heading">
        <h3 class="panel-title">Connections</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = [];
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width'] = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to'] = \App\Facades\LibrenmsConfig::get('time.now');
        $graph_array['type'] = 'device_bigip_ltm_allvs_conns';
        require 'includes/html/print-graphrow.inc.php';
        ?>
    </div>
</div>

<div class="panel panel-default" id="bytesin">
    <div class="panel-heading">
        <h3 class="panel-title">Bytes In</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = [];
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width'] = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to'] = \App\Facades\LibrenmsConfig::get('time.now');
        $graph_array['type'] = 'device_bigip_ltm_allvs_bytesin';
        require 'includes/html/print-graphrow.inc.php';
        ?>
    </div>
</div>

<div class="panel panel-default" id="bytesout">
    <div class="panel-heading">
        <h3 class="panel-title">Bytes Out</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = [];
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width'] = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to'] = \App\Facades\LibrenmsConfig::get('time.now');
        $graph_array['type'] = 'device_bigip_ltm_allvs_bytesout';
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
        $graph_array['type'] = 'device_bigip_ltm_allvs_pktsin';
        require 'includes/html/print-graphrow.inc.php';
        ?>
    </div>
</div>

<div class="panel panel-default" id="pktsout">
    <div class="panel-heading">
        <h3 class="panel-title">Packets Out</h3>
    </div>
    <div class="panel-body">
        <?php
        $graph_array = [];
        $graph_array['device'] = $device['device_id'];
        $graph_array['height'] = '100';
        $graph_array['width'] = '215';
        $graph_array['legend'] = 'no';
        $graph_array['to'] = \App\Facades\LibrenmsConfig::get('time.now');
        $graph_array['type'] = 'device_bigip_ltm_allvs_pktsout';
        require 'includes/html/print-graphrow.inc.php';
        ?>
    </div>
</div>

