<?php

echo 'Raisecom';

$multiplier = 1;
$divisor = 1000;
foreach ($pre_cache['rosMgmtOpticalTransceiverDDMTable'] as $index => $data) {
    foreach ($data as $key => $value) {
        if (($key == 'transceiverTemperature') && is_numeric($value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParameterValue']) && ($value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverDDMValidStatus'] == 1)) {
            $oid = '.1.3.6.1.4.1.8886.60.18.1.2.2.1.1.2.' . $index . '.1';
            $sensor_type = 'rosMgmtOpticalTransceiverTemperature';
            $port = PortCache::getByIfIndex(str_replace('1.', '', $index), $device['device_id']);
            $descr = $port?->ifDescr . ' Transceiver Temperature';
            $low_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamLowAlarmThresh'] / $divisor;
            $low_warn_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamLowWarningThresh'] / $divisor;
            $warn_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamHighWarningThresh'] / $divisor;
            $high_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamHighAlarmThresh'] / $divisor;
            $current = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParameterValue'] / $divisor;
            $entPhysicalIndex = $index;
            $entPhysicalIndex_measured = 'ports';
            discover_sensor(null, 'temperature', $device, $oid, 'tx-' . $index, $sensor_type, $descr, $divisor, $multiplier, $low_limit, $low_warn_limit, $warn_limit, $high_limit, $current, 'snmp', $entPhysicalIndex, $entPhysicalIndex_measured);
        }
    }
}
