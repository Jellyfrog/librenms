<?php

use App\Models\Sensor;

require_once 'includes/discovery/functions.inc.php';

if (isset($agent_data['hddtemp']) && $agent_data['hddtemp'] != '|') {
    $disks = explode('||', trim($agent_data['hddtemp'], '|'));
    echo 'hddtemp: ';

    $diskcount = 0;
    foreach ($disks as $disk) {
        [$blockdevice,$descr,$temperature,$unit] = explode('|', $disk, 4);
        $diskcount++;
        $temperature = trim(str_replace('C', '', $temperature));
        if (is_numeric($temperature)) {
            discover_sensor(null, 'temperature', $device, '', $diskcount, 'hddtemp', "$blockdevice: $descr", '1', '1', null, null, null, null, $temperature, 'agent');
            Sensor::where('sensor_index', $diskcount)
                ->where('sensor_class', 'temperature')
                ->where('poller_type', 'agent')
                ->where('device_id', $device['device_id'])
                ->update(['sensor_current' => $temperature]);
            $tmp_agent_sensors = Sensor::where('sensor_index', $diskcount)
                ->where('device_id', $device['device_id'])
                ->where('sensor_class', 'temperature')
                ->where('poller_type', 'agent')
                ->where('sensor_deleted', 0)
                ->first();
            $tmp_agent_sensors['new_value'] = $temperature;
            $agent_sensors[] = $tmp_agent_sensors;
            unset($tmp_agent_sensors);
        }
    }

    echo "\n";
}//end if
