This document helps you add wireless sensors for your
new wireless device.

At this time, we have support for the wireless metrics below,
together with the units in which we expect the data:

| Type        | Measurement | Interface                    | Description                                                                                     |
| ----------- | ----------- | ---------------------------- | ----------------------------------------------------------------------------------------------- |
| ap-count    | %           | WirelessApCountDiscovery     | The number of APs attached to this controller                                                   |
| capacity    | %           | WirelessCapacityDiscovery    | The % of operating rate vs theoretical max                                                      |
| ccq         | %           | WirelessCcqDiscovery         | The Client Connection Quality                                                                   |
| channel     | count       | WirelessChannelDiscovery     | The channel, use of frequency is preferred                                                      |
| cell        | count       | WirelessCellDiscovery        | The cell in a multicell technology                                                              |
| clients     | count       | WirelessClientsDiscovery     | The number of clients connected to/managed by this device                                       |
| distance    | km          | WirelessDistanceDiscovery    | The distance of a radio link in Kilometers                                                      |
| error-rate  | bps         | WirelessErrorRateDiscovery   | The rate of errored packets or bits, etc                                                        |
| error-ratio | %           | WirelessErrorRatioDiscovery  | The percent of errored packets or bits, etc                                                     |
| errors      | count       | WirelessErrorsDiscovery      | The total bits of errored packets or bits, etc                                                  |
| frequency   | MHz         | WirelessFrequencyDiscovery   | The frequency of the radio in MHz, channels can be converted                                    |
| mse         | dB          | WirelessMseDiscovery         | The Mean Square Error                                                                           |
| noise-floor | dBm         | WirelessNoiseFloorDiscovery  | The amount of noise received by the radio                                                       |
| power       | dBm         | WirelessPowerDiscovery       | The power of transmit or receive, including signal level                                        |
| quality     | %           | WirelessQualityDiscovery     | The % of quality of the link, 100% = perfect link                                               |
| rate        | bps         | WirelessRateDiscovery        | The negotiated rate of the connection (not data transfer)                                       |
| rssi        | dBm         | WirelessRssiDiscovery        | The Received Signal Strength Indicator                                                          |
| snr         | dB          | WirelessSnrDiscovery         | The Signal to Noise ratio, which is signal - noise floor                                        |
| sinr        | dB          | WirelessSinrDiscovery        | The Signal-to-Interference-plus-Noise Ratio                                                     |
| rsrq        | dB          | WirelessRsrqDiscovery        | The Reference Signal Received Quality                                                           |
| rsrp        | dBm         | WirelessRsrpDiscovery        | The Reference Signals Received Power                                                            |
| xpi         | dBm         | WirelessXpiDiscovery         | The Cross Polar Interference values                                                             |
| ssr         | dB          | WirelessSsrDiscovery         | The Signal strength ratio, the ratio(or difference) of Vertical rx power to Horizontal rx power |
| utilization | %           | WirelessUtilizationDiscovery | The % of utilization compared to the current rate                                               |

You must create a new OS class for your os, if one does not exist
under `LibreNMS/OS`.  The name of this file must be the os name in
camel case, for example `airos -> Airos`, `ios-wlc -> IosWlc`.

Your new OS class must extend LibreNMS\OS and implement the
interfaces for the sensors that your os supports.

```php
namespace LibreNMS\OS;

use LibreNMS\Device\WirelessSensor;
use LibreNMS\Interfaces\Discovery\Sensors\WirelessClientsDiscovery;
use LibreNMS\OS;

class Airos extends OS implements WirelessClientsDiscovery
{
    public function discoverWirelessClients()
    {
        $oid = '.1.3.6.1.4.1.41112.1.4.5.1.15.1'; //UBNT-AirMAX-MIB::ubntWlStatStaCount.1
        return array(
            new WirelessSensor('clients', $this->getDeviceId(), $oid, 'airos', 1, 'Clients')
        );
    }
}
```

All discovery interfaces make it necessary to return an array of WirelessSensor objects.

`new WirelessSensor()` Accepts these arguments:

- `$type =` Mandatory. This is the sensor class from the table above (i.e humidity).
- `$device_id =` Mandatory. You can get this value with $this->getDeviceId()
- `$oids =` Mandatory. This must be the numerical OID at which the data
  is, i.e .1.2.3.4.5.6.7.0. If this is an array of oids,
  specify an $aggregator.
- `$subtype =` Mandatory. This must be the OS name, i.e airos.
- `$index =` Mandatory. This must be unique for this sensor type, device and subtype.
  Usually, it is the index from the walked table. It can also be
  the name of the OID, if it is a single value.
- `$description =` Mandatory. This value tells about the sensor.
  The user sees it. If this is a per-ssid statistic, `SSID:
  $ssid` is applicable here
- `$current =` The default is null. You can use this to set the current value at discovery.
  If this is null, the system polls the values immediately. If they do
  not return valid value(s), the system does not discover the
  sensor. When you supply a value here, this means that you already made sure
  that this sensor is valid.
- `$multiplier =` The default is 1. The system multiplies the returned value by this.
- `$divisor =` The default is 1. The system divides the returned value by this.
- $aggregator = The default is sum. Valid values: sum, avg. This
  combines multiple values from multiple oids into one.
- `$access_point_id =` The default is null. If this is a wireless
  controller, you can attach sensors to entries in the access_points table.
- `$high_limit =` The default is null. Sets the high limit for the sensor.
  Alerting uses it to report sensors that are out of the range.
- `$low_limit =` The default is null. Sets the low threshold limit for the
  sensor. Alerting uses it to report sensors that are out of the range.
- `$high_warn =` The default is null. Sets the high warning limit for the
  sensor. Alerting uses it to report sensors that are almost out of the range.
- `$low_warn =` The default is null. Sets the low warning limit for the
  sensor. Alerting uses it to report sensors that are almost out of the range.
- `$entPhysicalIndex =` The default is null. Sets the entPhysicalIndex
  to look up more hardware, if available.
- `$entPhysicalIndexMeasured =` The default is null. Sets the type of
  entPhysicalIndex used, i.e ports.

Polling occurs automatically, from the discovered data.  If
you must replace polling, you can implement the
applicable polling interface in `LibreNMS/Interfaces/Polling/Sensors`.
Do not use the polling interfaces if it is not necessary.

Graphs for wireless sensors occur automatically. Custom
graphs are not necessary and not supported.
