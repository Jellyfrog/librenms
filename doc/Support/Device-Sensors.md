# Device Sensors

LibreNMS has a standard for device sensors. The sensors are divided
into categories. This document helps users understand device sensors
in general. If you need help with the development of sensors for a
device, refer to the [Contributing + Developing section](../Developing/os/Health-Information.md).

## Health Sensors

You can change the High and Low values of these sensors in the Web UI.
Go to the device settings -> Health. There you can set your own
High and Low values. The list of these sensors is
[here](../Developing/os/Health-Information.md)

!!! note
    The manufacturers define some values. The system calculates other
    values automatically when you add the device into librenms. Each
    environment is different and can make user input necessary.

## Wireless Sensors

Some wireless devices have High and Low values for sensors. You can
change them in the Web UI. Go to the device settings -> Wireless Sensors.
There you can set your own High and Low values. The list of these
sensors is [here](../Developing/os/Wireless-Sensors.md)

!!! note
    The manufacturers define some values. The system calculates other
    values automatically when you add the device into librenms. Each
    environment is different and can make user input necessary.

## State Sensors

These sensors record the state of a health sensor. You can use the
state for alerting. For example:

- Drive Status
- Memory Status
- Power Supply Status

The system maps the state to one of these values:

```
0 = OK
1 = Warning
2 = Critical
3 = Unknown
```

## Alerting Sensors

These alert rules are in the Alert Rules Collection. The
alert rules below are the default alert rules. The alerts collection
contains more alert rules for specified devices.

**Sensor Over Limit Alert Rule:**  Sends an alert for each sensor value that
is more than the limit.

**Sensor Under Limit Alert Rule:** Sends an alert for each sensor value that
is less than the limit.

!!! note
    You can set these limits in the device settings in the Web UI.

**State Sensor Critical:** Sends an alert for each state that returns critical = 2

**State Sensor Warning:** Sends an alert for each state that returns warning = 1

**Wireless Sensor Over Limit Alert Rule:** Sends an alert for the sensors that
are in the device settings under Wireless.

**Wireless Sensor Under Limit Alert Rule:** Sends an alert for the sensors that
are in the device settings under Wireless.
