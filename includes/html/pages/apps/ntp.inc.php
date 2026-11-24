<?php
/*
 * LibreNMS module to capture statistics from the CISCO-NTP-MIB
 *
 * Copyright (c) 2016 Aaron Daniels <aaron@daniels.id.au>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

$component = new LibreNMS\Component();
$options = [];
$options['filter']['ignore'] = ['=', 0];
$options['type'] = 'ntp';
$components = $component->getComponents(null, $options);

print_optionbar_start();

$view_options = [
    'all' => 'All',
    'error' => 'Error',
];
$graph_options = [
    'none' => 'No Graphs',
    'stratum' => 'Stratum',
    'offset' => 'Offset',
    'delay' => 'Delay',
    'dispersion' => 'Dispersion',
];

$vars['view'] ??= 'all';
$vars['graph'] ??= 'none';

echo '<span style="font-weight: bold;">NTP Peers</span> &#187; ';

// The menu option - on the left
$sep = '';
foreach ($view_options as $option => $text) {
    if (empty($vars['view'])) {
        $vars['view'] = $option;
    }
    echo $sep;
    if ($vars['view'] == $option) {
        echo "<span class='pagemenu-selected'>";
    }
    echo generate_link($text, $vars, ['view' => $option]);
    if ($vars['view'] == $option) {
        echo '</span>';
    }
    $sep = ' | ';
}

// The status option - on the right
echo '<div class="pull-right">';
$sep = '';
foreach ($graph_options as $option => $text) {
    if (empty($vars['graph'])) {
        $vars['graph'] = $option;
    }
    echo $sep;
    if ($vars['graph'] == $option) {
        echo "<span class='pagemenu-selected'>";
    }

    echo generate_link($text, $vars, ['graph' => $option]);
    if ($vars['graph'] == $option) {
        echo '</span>';
    }
    $sep = ' | ';
}
unset($sep);
echo '</div>';
print_optionbar_end();


// the table sorts, searches and paginates the peers in the browser
$rows = [];
foreach ($components as $device_id => $peers) {
    $device = DeviceCache::get($device_id);
    foreach ($peers as $component_id => $peer) {
        if ($vars['view'] == 'error' && $peer['status'] != 2) {
            continue;
        }

        $graphs = [];
        if (isset($graph_options[$vars['graph']]) && $vars['graph'] != 'none') {
            $graphs = \LibreNMS\Util\Html::graphRow(['device' => $device_id, 'type' => 'device_ntp_' . $vars['graph'], 'width' => 80, 'height' => 20]);
        }

        $rows[] = [
            'id' => $component_id,
            'device' => \LibreNMS\Util\Url::deviceLink($device, null, ['tab' => 'apps', 'app' => 'ntp']),
            'hostname' => $device->hostname,
            'peer' => $peer['peer'],
            'stratum' => $peer['stratum'],
            'error' => $peer['error'],
            'failed' => $peer['status'] == 2,
            'graphs' => $graphs,
        ];
    }
}

echo Blade::render(<<<'BLADE'
<x-data-table name="ntp" mode="local" :rows="$rows" :sort="['hostname' => 'asc']" :panel="false">
    <x-data-table.column field="hostname" :label="__('Device')" x-html="row.device" ::class="row.failed && 'danger'" />
    <x-data-table.column field="peer" :label="__('Peer')" />
    <x-data-table.column field="stratum" :label="__('Stratum')" />
    <x-data-table.column field="error" :label="__('Error')" />
    @if($showGraphs)
        <x-data-table.column field="graphs" :label="__('Graphs')" :sortable="false" :searchable="false">
            <div class="tw:flex tw:gap-1"><template x-for="(graph, index) in row.graphs" :key="index"><div x-html="graph"></div></template></div>
        </x-data-table.column>
    @endif
</x-data-table>
BLADE, ['rows' => $rows, 'showGraphs' => $vars['graph'] != 'none']);
