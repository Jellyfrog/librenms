# Sensor State Support

## Introduction

This section tells you how to implement support for sensor state. 
It also gives the basic concepts of sensor state monitoring.

LibreNMS makes sensor state monitoring simple. It converts raw values 
into clear generic states, such as "OK", "Warning", "Critical", and 
"Unknown". This gives a consistent view and easier analysis.

## Key Concepts

For sensor state monitoring, 4 DB tables are important. 
These tables are a bridge between the raw information that each sensor gives 
and the standard representation (generic state) that LibreNMS uses 
to show data and make alerts.

### Table: sensors

*Each time the system polls a sensor, it must know which
sensor (of each type) it must poll, and its description, 
the oid at which this sensor is, the class of the sensor, etc.*

### Table: sensors_to_state_indexes

*This is where the sensor_id is mapped 
to a state_index_id.*

### Table: state_indexes

*This is where we keep the state information that we monitor.*

### Table: state_translations

*This is where we map the possible returned state sensor values to a
generic LibreNMS value. This makes the display and the alerts more
generic. We also map these values to the true state
sensor(state_index) from which these values come.*

*The LibreNMS generic states come from Nagios:*

```
0 = OK
1 = Warning
2 = Critical
3 = Unknown
```

 ### Generic States translations

LibreNMS can do sensor states in different ways. SNMP can show them 
as strings or as numbers. 

If the sensor state input is a string (i.e. "ONLINE"), 
librenms uses the 'descr' field, and then converts it to the applicable 
generic state (0, 1, 2 or 3)
- { value: 4, **descr: online**, graph: 1, **generic: 0** }

If the sensor state input is a number (i.e. "4", which represents the offline state), 
librenms uses the 'value' field, and then converts it to the applicable 
generic state (0, 1, 2 or 3).  
- { **value: 0**, descr: offline, graph: 1, **generic: 2** }

!!! note
    Here, the descr field is a label to show the value on the screen. 
    It is not an input for the conversion to a generic state, because the state input
    is a number.

## YAML Example

For YAML based state discovery:

```yaml
modules:
    sensors:
        state:
            data:
                -
                    oid: NETBOTZV2-MIB::dryContactSensorTable
                    value: NETBOTZV2-MIB::dryContactSensorValue
                    num_oid: '.1.3.6.1.4.1.5528.100.4.2.1.1.2.{{ $index }}'
                    descr: NETBOTZV2-MIB::dryContactSensorLabel
                    group: Contact Sensors
                    index: 'dryContactSensor.{{ $index }}'
                    state_name: NETBOTZV2-MIB::dryContactSensor
                    states:
                        - { value: -1, generic: 3, graph: 0, descr: 'null' }
                        - { value:  0, generic: 0, graph: 0, descr: open }
                        - { value:  1, generic: 2, graph: 0, descr: closed }
                -
                    oid: NETBOTZV2-MIB::doorSwitchSensorTable
                    value: NETBOTZV2-MIB::doorSwitchSensorValue
                    num_oid: '.1.3.6.1.4.1.5528.100.4.2.2.1.2.{{ $index }}'
                    descr: NETBOTZV2-MIB::doorSwitchSensorLabel
                    group: Switch Sensors
                    index: 'doorSwitchSensor.{{ $index }}'
                    state_name: NETBOTZV2-MIB::doorSwitchSensor
                    states:
                        - { value: -1, generic: 3, graph: 0, descr: 'null' }
                        - { value:  0, generic: 0, graph: 0, descr: open }
                        - { value:  1, generic: 2, graph: 0, descr: closed }
                -
                    oid: NETBOTZV2-MIB::cameraMotionSensorTable
                    value: NETBOTZV2-MIB::cameraMotionSensorValue
                    num_oid: '.1.3.6.1.4.1.5528.100.4.2.3.1.2.{{ $index }}'
                    descr: NETBOTZV2-MIB::cameraMotionSensorLabel
                    group: Camera Motion Sensors
                    index: 'cameraMotionSensor.{{ $index }}'
                    state_name: NETBOTZV2-MIB::cameraMotionSensor
                    states:
                        - { value: -1, generic: 3, graph: 0, descr: 'null' }
                        - { value:  0, generic: 0, graph: 0, descr: noMotion }
                        - { value:  1, generic: 2, graph: 0, descr: motionDetected }
                -
                    oid: NETBOTZV2-MIB::otherStateSensorTable
                    value: NETBOTZV2-MIB::otherStateSensorErrorStatus
                    num_oid: '.1.3.6.1.4.1.5528.100.4.2.10.1.3.{{ $index }}'
                    descr: NETBOTZV2-MIB::otherStateSensorLabel
                    index: '{{ $index }}'
                    state_name: NETBOTZV2-MIB::otherStateSensorErrorStatus
                    states:
                        - { value: 0, generic: 0, graph: 0, descr: normal }
                        - { value: 1, generic: 1, graph: 0, descr: info }
                        - { value: 2, generic: 1, graph: 0, descr: warning }
                        - { value: 3, generic: 2, graph: 0, descr: error }
                        - { value: 4, generic: 2, graph: 0, descr: critical }
                        - { value: 5, generic: 2, graph: 0, descr: failure }
```

## Advanced Example

For advanced state discovery:

This example is based on a Cisco power supply sensor. It is all
that is necessary for sensor state support for Cisco power supplies in Cisco
switches. The file must be in 
/includes/discovery/sensors/state/cisco.inc.php.

```php
<?php

$oids = SnmpQuery::hideMib()->walk('CISCO-ENVMON-MIB::ciscoEnvMonSupplyStatusTable')->valuesByIndex;

if (!empty($oids)) {
    //Create State Index
    $state_name = 'CISCO-ENVMON-MIB::ciscoEnvMonSupplyState';
    $states = [
        ['value' => 1, 'generic' => 0, 'graph' => 0, 'descr' => 'normal'],
        ['value' => 2, 'generic' => 1, 'graph' => 0, 'descr' => 'warning'],
        ['value' => 3, 'generic' => 2, 'graph' => 0, 'descr' => 'critical'],
        ['value' => 4, 'generic' => 3, 'graph' => 0, 'descr' => 'shutdown'],
        ['value' => 5, 'generic' => 3, 'graph' => 0, 'descr' => 'notPresent'],
        ['value' => 6, 'generic' => 2, 'graph' => 0, 'descr' => 'notFunctioning'],
    ];
    create_state_index($state_name, $states);

    $num_oid = '.1.3.6.1.4.1.9.9.13.1.5.1.3.';
    foreach ($oids as $index => $entry) {
        //Discover Sensors
        discover_sensor(null, 'state', $device, $num_oid.$index, $index, $state_name, $entry['ciscoEnvMonSupplyStatusDescr'], '1', '1', null, null, null, null, $entry['ciscoEnvMonSupplyState'], 'snmp', $index);
    }
}
```
