<?php

echo 'Raisecom';

$multiplier = 1;
$divisor = 1000;
foreach ($pre_cache['rosMgmtOpticalTransceiverDDMTable'] as $index => $data) {
    foreach ($data as $key => $value) {
        if (($key == 'txPower') && is_numeric($value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParameterValue']) && ($value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverDDMValidStatus'] == 1)) {
            $oid = '.1.3.6.1.4.1.8886.1.18.2.2.1.1.2.' . $index . '.3';
            $sensor_type = 'rosMgmtOpticalTransceiverTxPower';
            $port = PortCache::getByIfIndex(str_replace('1.', '', $index), $device['device_id']);
            $descr = $port?->ifDescr . ' Transmit Power';
            $low_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamLowAlarmThresh'] / $divisor;
            $low_warn_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamLowWarningThresh'] / $divisor;
            $warn_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamHighWarningThresh'] / $divisor;
            $high_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamHighAlarmThresh'] / $divisor;
            $current = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParameterValue'] / $divisor;
            $entPhysicalIndex = $index;
            $entPhysicalIndex_measured = 'ports';
            if ($port['ifAdminStatus'] == 'up') {
                discover_sensor(null, 'dbm', $device, $oid, 'tx-' . $index, $sensor_type, $descr, $divisor, $multiplier, $low_limit, $low_warn_limit, $warn_limit, $high_limit, $current, 'snmp', $entPhysicalIndex, $entPhysicalIndex_measured);
            }
        }
        if (($key == 'rxPower') && is_numeric($value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParameterValue']) && ($value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverDDMValidStatus'] != 0)) {
            $oid = '.1.3.6.1.4.1.8886.1.18.2.2.1.1.2.' . $index . '.4';
            $sensor_type = 'rosMgmtOpticalTransceiverRxPower';
            $port = PortCache::getByIfIndex(str_replace('1.', '', $index), $device['device_id']);
            $descr = $port?->ifDescr . ' Receive Power';
            $low_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamLowAlarmThresh'] / $divisor;
            $low_warn_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamLowWarningThresh'] / $divisor;
            $warn_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamHighWarningThresh'] / $divisor;
            $high_limit = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParamHighAlarmThresh'] / $divisor;
            $current = $value['ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverParameterValue'] / $divisor;
            $entPhysicalIndex = $index;
            $entPhysicalIndex_measured = 'ports';
            if ($port['ifAdminStatus'] == 'up') {
                discover_sensor(null, 'dbm', $device, $oid, 'rx-' . $index, $sensor_type, $descr, $divisor, $multiplier, $low_limit, $low_warn_limit, $warn_limit, $high_limit, $current, 'snmp', $entPhysicalIndex, $entPhysicalIndex_measured);
            }
        }
    }
}
