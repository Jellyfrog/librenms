## Sensors

This document helps you add health / sensor
information for your new device.

At this time, we have support for the health metrics below, together with
the units in which we expect the data:

| Class                           | Measurement                 |
| ------------------------------- | --------------------------- |
| airflow                         | cfm                         |
| ber                             | ratio                       |
| bitrate                         | bps                         |
| charge                          | %                           |
| chromatic_dispersion            | ps/nm                       |
| cooling                         | W                           |
| count                           | #                           |
| current                         | A                           |
| dbm                             | dBm                         |
| delay                           | s                           |
| eer                             | eer                         |
| fanspeed                        | rpm                         |
| frequency                       | Hz                          |
| humidity                        | %                           |
| load                            | %                           |
| loss                            | %                           |
| percent                         | %                           |
| power                           | W                           |
| power_consumed                  | kWh                         |
| power_factor                    | ratio                       |
| pressure                        | kPa                         |
| quality_factor                  | dB                          |
| runtime                         | Min                         |
| signal                          | dBm                         |
| snr                             | SNR                         |
| state                           | #                           |
| temperature                     | C                           |
| tv_signal                       | dBmV                        |
| voltage                         | V                           |
| waterflow                       | l/m                         |
| signal_loss                     | dB                          |

### Simple health discovery

You can define health / sensor discovery with YAML
files. Then it is not necessary to know how to write PHP.

> DISPLAY-HINTS are disabled. Thus, make sure that you use the
> correct divisor / multiplier, if applicable.

All yaml files are in
`resources/definitions/os_discovery/$os.yaml`. It is not always possible to define the information
here. It depends much on vendors that make
good MIBs. Only snmp walks are supported,
and you must give a good table that the system can go through, and that contains
all the necessary data. We use netbotz as an example here.

`resources/definitions/os_discovery/netbotz.yaml`

```yaml
modules:
    sensors:
        airflow:
            options:
                skip_value_lt: 0
            data:
                -
                    oid: NETBOTZV2-MIB::airFlowSensorTable
                    value: NETBOTZV2-MIB::airFlowSensorValue
                    divisor: 10
                    num_oid: '.1.3.6.1.4.1.5528.100.4.1.5.1.2.{{ $index }}'
                    descr: '{{ NETBOTZV2-MIB::airFlowSensorLabel }}'
                    index: 'airFlowSensorValue.{{ $index }}'
```

Make sure that you use the format MIB-NAME::OID for all references to OIDs.

For `data:` you have these options:

The only sensor that we defined here is airflow. The available options
are:

- `oid` (mandatory): This is the name of the table that you want to snmp walk for data, with the MIB-NAME before it, That is, `NETBOTZV2-MIB::airFlowSensorTable`.
- `value` (optional): This is the key in the table that contains
  the value, with the MIB-NAME before it, That is, `NETBOTZV2-MIB::airFlowSensorValue`. If you do not give it, the system uses `oid`.
- `num_oid` (mandatory for PullRequests): If you do not give this parameter, the discovery
  procedure calculates it automatically. But this parameter is mandatory when you
  submit a pull request. This is the numerical OID that contains
  `value`. It must usually include `{{ $index }}`.
the string to the equivalent OID representation.
- `divisor` (optional): This is the divisor to use against the returned `value`.
- `multiplier` (optional): This is the multiplier to use against the returned `value`.
- `low_limit` (optional): This is the critical low threshold for
  `value` (used in alerting). If you specify an OID, the system
  uses the divisor / multiplier.
- `low_warn_limit` (optional): This is the warning low threshold for
  `value` (used in alerting). If you specify an OID, the system
  uses the divisor / multiplier.
- `warn_limit` (optional): This is the warning high threshold for
  `value` (used in alerting). If you specify an OID, the system
  uses the divisor / multiplier.
- `high_limit` (optional): This is the critical high threshold for
  `value` (used in alerting). If you specify an OID, the system
  uses the divisor / multiplier.
- `skip_limits_calc` (optional): This is a true/false flag that
  permits raw values when limits come from OID's. If set to true,
  the system does not apply the divisor / multiplier to `_limit` values. But
  it applies `user_func` to `_limit` values
- `descr` (mandatory): The visible label for this sensor. It can be a
  key in the table, or a static string, optionally with `{{ index }}`.
- `group` (optional): Puts sensors together in a group in the webui,
  with this text. If you do not specify this, the sensors go into
  the default group. If group is set to `transceiver`, the sensor shows with the port,
  not with all the generic sensors (you must also set `entPhysicalIndex` to ifIndex)
- `index` (optional): This is the index value that we use to
  identify this sensor. The system replaces `{{ $index }}` with the numeric
  `index` of this row in the table of the snmp walk.
- `skip_values` (optional): This is an array of values that the system must
  ignore (see the note below).
- `skip_value_lt` (optional): If the sensor value is less than this, no discovery occurs.
- `skip_value_gt` (optional): If the sensor value is more than this, no discovery occurs.
- `entPhysicalIndex` and `entPhysicalIndex_measured` (optional) : If the
  sensor belongs to a physical entity, you can attach them here. The
  supported variants at this time are :
    - `entPhysicalIndex` contains the entPhysicalIndex from the entPhysical table, and `entPhysicalIndex_measured` is NULL
    - `entPhysicalIndex` contains the "ifIndex" value of the attached port, and `entPhysicalIndex_measured` contains "ports"
- `user_func` (optional): You can give a function name through which the system
  processes the sensor value (that is, to convert fahrenheit to
  celsius, use `fahrenheit_to_celsius`)
- `snmp_flags` (optional): This sets the flags sent to snmpwalk. It 
  replaces the flags set on the sensor type and os.  The default is `'-OQUb'`.
  A usual problem is string indexes. The setting `'-OQUsbe'` changes them to 
  numeric oids. The setting `['-OQUsbe', '-Pu']` also permits _ in oid names. More information is
  in the [Man Page](https://linux.die.net/man/1/snmpcmd)
- `rrd_type` (optional): You can change the type of the RRD file that the system creates to
  keep the data. The default type is GAUGE. More details are here:
  https://oss.oetiker.ch/rrdtool/doc/rrdcreate.en.html

For `options:` these are available:

- `divisor`: This is the divisor for the returned `value`.
- `multiplier`: This is the multiplier for the returned `value`.
- `skip_values`: This is an array of values that the system must ignore (see the note below).
- `skip_value_lt`: If the sensor value is less than this, no discovery occurs.
- `skip_value_gt`: If the sensor value is more than this, no discovery occurs.

You can use multiple variables in the sensor definition. The syntax
is `{{ MIB-NAME::variable }}`. You can use each oid in the current table, and
also pre-fetched data. The index ($index) and the sub_indexes (when
the oid has an index multiple times) are also available: if
$index="1.20", then $subindex0="1" and $subindex1="20".

To get data not available to your sensor, you can use `additional_oids`.

!!! note
    Use `additional_oids` only when your sensor does not get the data.

 You can also use `additional_oids` in a class.
 This is the recommended method if only the class uses the `additional_oids`.
 See `additional_oids` in the `temperature` class below, and also `additional_oids` on the `sensors` level.
 
!!! note
     Use only one `additional_oids` statement for the same oid. This is only an example that shows the two cases.

```
sensors:
    additional_oids:
        data:
            -
                oid:
                    - Stulz-WIB8000-MIB::unitsettingName
    temperature:
        additional_oids:
            data:
                -
                    oid:
                        - Stulz-WIB8000-MIB::unitsettingName
        data:
            -
                oid: Stulz-WIB8000-MIB::unitTemperature
                value: Stulz-WIB8000-MIB::unitTemperature
                num_oid: '.1.3.6.1.4.1.29462.10.2.1.1.1.1.1.1.1.1170.{{ $index }}'
                index: 'unitTemperature.{{ $index }}'
                descr: 'Unit {{ Stulz-WIB8000-MIB::unitsettingName:0-1 }} temp'
                divisor: 10
            -
                oid: Stulz-WIB8000-MIB::unitSupplyAirTemperature
                value: Stulz-WIB8000-MIB::unitSupplyAirTemperature
                num_oid: '.1.3.6.1.4.1.29462.10.2.1.1.1.1.1.1.1.1193.{{ $index }}'
                index: 'unitSupplyAirTemperature.{{ $index }}'
                descr: 'Unit {{ Stulz-WIB8000-MIB::unitsettingName:0-1 }} supply temp'
                divisor: 10
```

To get access to a string in an index, you can use `{{ $index_string }}`,
optionally with a format string at the end that specifies how to get the string.
`{{ $index_string:nns }}` ignores two numeric indexes and returns the string after them.
`{{ $index_string:nss }}` ignores one numeric index and one string index and returns
the next string after them.

#### Fetching values from other tables/oids

When you refer to an oid in a different table, the system uses the full index to match
the other table. If the indexes of the two tables do not match, you must
specify the indexes to use, by their index position, which starts with 0. The
data for the other table must already be fetched.

`{{ IF-MIB::ifName:2 }}`

This simple example uses the 3rd (0 is the first) index value from
the current table to get the IF-MIB::ifName value from the data.

You can also specify multiple index values, with a
range or a list of index positions.

Range: `{{ IP-MIB::ipAddressPrefixOrigin:0-3 }}`
List: `{{ IP-MIB::ipAddressPrefixOrigin:2.3.1.4 }}`

#### Skipping rows of the returned data

You can remove rows of the returned data, to discover only valid sensors.
This is frequently useful when devices always return all possible sensors, or
mix sensor types in a single table.

> `skip_values` can also compare items in the OID table with
> values. The system uses the index of the sensor to get the value
> from the OID, unless a target index is added to the end of the OID.
> You can also examine fields from the device.
> Chained comparisons operate as a logical OR. Thus, only
> one of them must match for the system to ignore that sensor
> during discovery. An example is below:

```yaml
                    skip_values:
                    -
                      oid: STE2-MIB::sensUnit
                      op: '!='
                      value: 4
                    -
                      oid: STE2-MIB::sensConfig.0
                      op: '!='
                      value: 1
                    -
                      device: STE2-MIB::hardware
                      op: 'contains'
                      value: 'rev2'
```

`op` can be one of these operators :

> =, !=, ==, !==, <=, >=, <, >,
> starts, ends, contains, regex, in_array, not_starts,
> not_ends, not_contains, not_regex, not_in_array, exists

Example:

```yaml
                    skip_values:
                    -
                      oid: MIB-NAME::sensorName
                      op: 'not_in_array'
                      value: ['sensor1', 'sensor2']
```

```yaml
                    skip_values:
                    -
                      oid: MIB-NAME::sensorOptionalOID
                      op: 'exists'
                      value: false
```

```yaml
        temperature:
            additional_oids:
                data:
                    -
                        oid:
                            - ENTITY-MIB::entPhysicalName
            data:
                -
                    oid: HUAWEI-ENTITY-EXTENT-MIB::hwOpticalModuleInfoTable
                    value: HUAWEI-ENTITY-EXTENT-MIB::hwEntityOpticalTemperature
                    descr: '{{ ENTITY-MIB::entPhysicalName }}'
                    index: '{{ $index }}'
                    skip_values:
                        -
                            oid: HUAWEI-ENTITY-EXTENT-MIB::hwEntityOpticalMode
                            op: '='
                            value: '1'
```

If you cannot use yaml for the sensor discovery, you
probably must use Advanced health discovery.

### Advanced health discovery

If you cannot use the yaml files as above, you must create
the discovery code in php. If yaml is possible, we probably do not accept
php discovery, because the chance of later problems is much higher.
Thus, we strongly recommend yaml.

The directory structure for sensor information is
`includes/discovery/sensors/$class/$os.inc.php`. All
sensors use the same code format: collect sensor information
through SNMP, and then call the `discover_sensor()` function. State
sensors are an exception. They need more code. Sensor information is usually in an ENTITY
mib that the device vendor supplies, in the form of a table. You can also use other mib
tables.

`discover_sensor()` Accepts these arguments:

- &$valid = This is always null. This is not used.
- $class = Mandatory. This is the sensor class from the table above (that is, humidity).
- $device = Mandatory. This is the $device array.
- $oid = Mandatory. This must be the numerical OID at which the data
  is, that is, .1.2.3.4.5.6.7.0
- $index = Mandatory. This must be unique for this sensor class, device
  and type. Usually, it is the index from the walked table. It can also be
  the name of the OID, if it is a single value.
- $type = Mandatory. This must be the OS name, that is, pulse.
- $descr = Mandatory. This value tells about the sensor. Some
  devices give names to use.
- $divisor = The default is 1. The system divides the returned value by this.
- $multiplier = The default is 1. The system multiplies the returned value by this.
- $low_limit = The default is null. Sets the low threshold limit for the
  sensor. Alerting uses it to report sensors that are out of the range.
- $low_warn_limit = The default is null. Sets the low warning limit for
  the sensor. Alerting uses it to report sensors that are almost out of the range.
- $warn_limit = The default is null. Sets the high warning limit for the
  sensor. Alerting uses it to report sensors that are almost out of the range.
- $high_limit = The default is null. Sets the high limit for the sensor.
  Alerting uses it to report sensors that are out of the range.
- $current = The default is null. You can use this to set the current value at
  discovery. The Poller updates this at the next poll cycle.
- $poller_type = The default is snmp. Items such as the unix-agent can set
  different values. But usually, keep this as snmp.
- $entPhysicalIndex = The default is null. Sets the entPhysicalIndex
  to look up more hardware, if available.
- $entPhysicalIndex_measured = The default is null. Sets the type of
  entPhysicalIndex used, that is, ports.
- $user_func = The default is null. You can give a function name through
  which the system processes the sensor value (that is, to convert fahrenheit
  to celsius, use `fahrenheit_to_celsius`)
- $group = The default is null. Puts sensors together in a group in the
  webui, with this text.
- $rrd_type = The default is 'GAUGE'. With this, you can change the type of the RRD
  file created for this sensor. More details are in the
  RRD documentation: https://oss.oetiker.ch/rrdtool/doc/rrdcreate.en.html

For most devices, this is all that is necessary to add
support for a sensor. Polling uses the data collected with
`discover_sensor()`. If custom polling is necessary, the file format
is similar to discovery:
`includes/polling/sensors/$class/$os.inc.php`. More snmp queries in
polling are possible, but do not use them
if it is not necessary. The value for the OID is already available as `$sensor_value`.

Graphs for sensors occur automatically. Custom graphs are
not necessary and not supported.

### Adding a new sensor class

You must add code for your new sensor class in these files:

- `LibreNMS/Enum/Sensor.php`: add the class. Find a free icon on [Font Awesome](https://fontawesome.com/icons?d=gallery&m=free)
- `doc/Developing/os/Health-Information.md`: documentation for each sensor class is mandatory.
- `includes/discovery/functions.inc.php`: optional - if good low_limit and high_limit values
can be estimated when a threshold from SNMP is not available, add a case for the sensor class
to the sensor_limit() and/or sensor_low_limit() functions.
- `LibreNMS/Util/ObjectCache.php`: optional - select the menu group for the sensor class.
- `includes/html/pages/device/overview.inc.php`: add `require 'overview/sensors/$class.inc.php'`
in the applicable order for the device overview page.
- `lang/en/sensors.php`: add names and units that persons can read for the sensor class
in English. You can also do this for other languages.

Create and fill new files for the sensor class in these locations:

- `includes/discovery/sensors/$class/`: create the folder that keeps the advanced php-based discovery
files. Not used for yaml discovery.
=======
- `includes/html/pages/device/overview.inc.php`: add `require 'overview/sensors/$class.inc.php'` in the applicable
order for the device overview page.
- `lang/en/sensors.php`: add names and units that persons can read for the sensor class in English. You
can also do this for other languages.

Create and fill new files for the sensor class in these locations:

- `includes/discovery/sensors/$class/`: create the folder that keeps the advanced php-based discovery files.
Not used for yaml discovery.
- `includes/html/graphs/device/$class.inc.php`: define the unit names used in RRDtool graphs.
- `includes/html/graphs/sensor/$class.inc.php`: define different [parameters](https://oss.oetiker.ch/rrdtool/doc/rrdgraph_graph.en.html) for RRDtool graphs.
- `includes/html/pages/device/health/$class.inc.php`
- `includes/html/pages/device/overview/sensors/$class.inc.php`
- `includes/html/pages/health/$class.inc.php`

#### Advanced health sensor example

This example shows how to build sensors with the advanced method. In this example, we
collect the optical power level (dBm) from Adva FSP150CC family MetroE devices. This example
applies when you know SNMP and MIBs.

The first line walks the cmEntityObject table to get information about the chassis and line cards. From
this information, we get the model type. This identifies the tables in the CM-Facility-Mib
that contain the ports. The program then reads the applicable table into the `$data`
array `adva_fsp150_ports`. This array has OID indexes for each port. We use
them later to identify our sensor OIDs.

Then we build our sensor discovery code. These are optical readings. Thus, we create the file
as the dBm sensor type in `includes/discover/sensors/dbm/adva_fsp150.inc.php`. Below is
a part of the code:

```php
$data = SnmpQuery::walk([
    'CM-FACILITY-MIB::cmEthernetTrafficPortTable',
    'CM-PERFORMANCE-MIB::cmEthernetTrafficPortStatsOPT',
    'CM-PERFORMANCE-MIB::cmEthernetTrafficPortStatsOPR',
])->valuesByIndex();

foreach ($data as $index => $entry) {
    if (isset($entry['CM-FACILITY-MIB::cmEthernetTrafficPortMediaType']) && $entry['CM-FACILITY-MIB::cmEthernetTrafficPortMediaType'] == 'fiber') {
        //Discover received power level
        $oidRx = '.1.3.6.1.4.1.2544.1.12.5.1.21.1.34.' . $index . '.3';
        $oidTx = '.1.3.6.1.4.1.2544.1.12.5.1.21.1.33.' . $index . '.3';
        $currentTx = $data[$index . '.3']['CM-PERFORMANCE-MIB::cmEthernetTrafficPortStatsOPT'] ?? null;
        $currentRx = $data[$index . '.3']['CM-PERFORMANCE-MIB::cmEthernetTrafficPortStatsOPR'] ?? null;
        if ($currentRx != 0 || $currentTx != 0) {
            $ifIndex = $entry['CM-FACILITY-MIB::cmEthernetTrafficPortIfIndex'] ?? 0;
            $ifName = PortCache::getByIfIndex($ifIndex)?->ifName;

            app('sensor-discovery')->discover(new \App\Models\Sensor([
                'poller_type' => $poller_type,
                'sensor_class' => 'dbm',
                'device_id' => $device['device_id'],
                'sensor_oid' => $oidRx,
                'sensor_index' => 'cmEthernetTrafficPortStatsOPR.' . $index,
                'sensor_type' => 'adva_fsp150,
                'sensor_descr' => $ifName . ' Rx Power',
                'sensor_divisor' => 1,
                'sensor_multiplier' => 1,
                'sensor_limit' => null,
                'sensor_limit_warn' => null,
                'sensor_limit_low' => null,
                'sensor_limit_low_warn' => null,
                'sensor_current' => $currentRx,
                'entPhysicalIndex' => $ifIndex,
                'entPhysicalIndex_measured' => 'ports',
            ]));

            app('sensor-discovery')->discover(new \App\Models\Sensor([
                'poller_type' => $poller_type,
                'sensor_class' => 'dbm',
                'device_id' => $device['device_id'],
                'sensor_oid' => $oidRx,
                'sensor_index' => 'cmEthernetTrafficPortStatsOPT.' . $index,
                'sensor_type' => 'adva_fsp150,
                'sensor_descr' => $ifName . ' Tx Power',
                'sensor_divisor' => 1,
                'sensor_multiplier' => 1,
                'sensor_limit' => null,
                'sensor_limit_warn' => null,
                'sensor_limit_low' => null,
                'sensor_limit_low_warn' => null,
                'sensor_current' => $currentTx,
                'entPhysicalIndex' => $ifIndex,
                'entPhysicalIndex_measured' => 'ports',
            ]));
        }
    }
}
```

First, the program goes through the index value of each port. On Advas, the ports have
the names Ethernet 1-1-1-1, 1-1-1-2, and so on. Their indexes are oid.1.1.1.1, oid.1.1.1.2, and so on in
the mib.

Then the program finds the table in which the port exists, and makes sure that the connector type is 'fiber'. The
full code has other port tables, which are not in the example, to keep it short. Copper
media does not have optical readings. Thus, if the media type is not fiber, we do no discovery for that port.

The next two lines build the OIDs to get the optical receive and transmit values, with the
`$index` for the port. With the OIDs, the program gets the current receive and transmit values
($currentRx and $currentTx) to make sure that the values are not 0. Not all SFPs collect digital
optical monitoring (DOM) data. On Adva, the transmit and receive values are both
0 if DOM is not available. 0 is a valid value for optical power. But it is very improbable that
both are 0 if DOM is present. If DOM is not available, the program stops discovery for
that port. This is the behavior of Adva. Other vendors can do this differently for
optics that do not supply DOM. Refer to the mibs of your vendor.

Then the program sets the values of $entPhysicalIndex and $entPhysicalIndex_measured. Here,
$entPhysicalIndex is set to the value of the `CM-FACILITY-MIB::cmEthernetTrafficPortIfIndex`.
Thus, it is attached to the port. With this, the sensor graphs also show on the
page of the attached port in the GUI, in addition to the Health page.

After that, the program uses a database call to get the description of the port. This
becomes the title of the graph in the GUI.

Last, the program calls `discover_sensor()` and passes the information collected in the steps
before. The `null` values are for the low, low warning, high, and high warning values. The Adva
MIB does not collect them.

To make sure that the code operates, run discovery manually with `lnms device:discover $device_id -m sensors`.
You can use `-v` to see the calls used during discovery, and `-d` to see debug output.
In the output under `#### Load disco module sensors ####`, you see a list of sensor types. A `+`
means that a sensor is added. A `-` means that one was deleted. A `.` means no change. If
there is nothing adjacent to the sensor type, the sensor was not discovered. There is also
information about changes to the database and the RRD files at the bottom.

```
[librenms@nms-test ~]$ lnms device:discover 2 -m sensors
LibreNMS Discovery
164.113.194.250 2 adva_fsp150

#### Load disco module core ####

>> Runtime for discovery module 'core': 0.0240 seconds with 66536 bytes
>> SNMP: [2/0.06s] MySQL: [3/0.00s] RRD: [0/0.00s]
#### Unload disco module core ####


#### Load disco module sensors ####
Pre-cache adva_fsp150:
 ENTITY-SENSOR: Caching OIDs: entPhysicalDescr entPhysicalName entPhySensorType entPhySensorScale entPhySensorPrecision entPhySensorValue entPhySensorOperStatus
Airflow:
Current: .
Charge:
Dbm: Adva FSP-150 dBm..
Fanspeed:
Frequency:
Humidity:
Load:
Power:
Power_consumed:
Power_factor:
Runtime:
Signal:
State:
Count:
Temperature: ..
Tv_signal:
Bitrate:
Voltage: .
Snr:
Pressure:
Cooling:
Delay:
Quality_factor:
Chromatic_dispersion:
Ber:
Eer:
Waterflow:
Percent:
Signal_loss:

>> Runtime for discovery module 'sensors': 3.9340 seconds with 190024 bytes
>> SNMP: [16/3.89s] MySQL: [36/0.03s] RRD: [0/0.00s]
#### Unload disco module sensors ####

Discovered in 5.521 seconds

SNMP [18/3.96s]: Get[8/0.81s] Getnext[0/0.00s] Walk[10/3.15s]
MySQL [41/0.03s]: Cell[10/0.01s] Row[-4/-0.00s] Rows[31/0.02s] Column[0/0.00s] Update[2/0.00s] Insert[2/0.00s] Delete[0/0.00s]
RRD [0/0.00s]: Update[0/0.00s] Create [0/0.00s] Other[0/0.00s]
```
