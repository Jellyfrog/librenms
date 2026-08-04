# Macros

Macros are short names for portions of rules, or for pure SQL with placeholders.

You can define macros with the `lnms` command. `config.php` is supported, but we do not recommend it. A macro can be true/false (a boolean test), or it can return a value (integer, float, string) that you can use in the rule.

Example: add a macro that returns the delta of a sensor:

```bash
lnms config:set alert.macros.rule.sensor_delta 'ABS(%sensors.sensor_current - %sensors.sensor_prev)'
```

Example: add a macro through `config.php` that is a boolean test:

```php
$config['alert']['macros']['rule']['is_debian'] = '%devices.features ~ "@debian@"';
```

You can then use these macros in the alerting rules. Example:

```bash
... macros.sensor_delta_current > 10 AND macros.rule.is_debian = 1 ...
```

## Writing Macros

The name of the macro sets the type of the macro. If the macro name ends with `_perc` or '_delta', the system reads it as an integer. Then a comparison of the value is possible. Each other name is a boolean test, which shows as a `yes` or `no` selection in the rule.

The macro can contain placeholders. When the system examines the rule, it replaces the placeholders with the true values. The placeholders have the prefix `%`, which represents the true value of the sensor, port, device, and so on. For example, the system replaces `%sensors.sensor_current` with the true value of the sensor. The prefix `%` is optional, but we recommend it to prevent ambiguity.

The contents of a macro can be any valid SQL statement or valid rule expression.

## Pre-defined Macros

### Billing

#### Over quota (Boolean)

Entity: `macros.bill_quota_over_quota`

Description: true or false if the bill is over quota.

Source: `((bills.total_data \/ bills.bill_quota)*100) && bills.bill_type = "quota"`

#### Over usage (Boolean)

Entity: `macros.bill_cdr_over_quota`

Description: true or false if the bill is over usage.

Source: `((bills.rate_95th \/ bills.bill_cdr)*100) && bills.bill_type = "cdr"`

### Components

### Component (Boolean)

Entity: `macros.component`

Description: Select only components that are not deleted, ignored or disabled.

Source: `(component.disabled = 0 && component.ignore = 0)`

### Component (Critical) (Boolean)

Entity: `macros.component_critical`

Description: Select only components that are in a critical state.

Source: `(component.status = 2 && macros.component)`

### Component (Up) (Boolean)

Entity: `macros.component_normal`

Description: Select only components that are in a normal state.

Source: `(component.status = 0 && macros.component)`


### Component (Warning) (Boolean)

Entity: `macros.component_warning`

Description: Select only components that are in a warning state.

Source: `(component.status = 1 && macros.component)`

### Device

#### Device (Boolean)

Entity: `macros.device`

Description: Select only devices that are not deleted, ignored or disabled.

Source: `(devices.disabled = 0 AND devices.ignore = 0)`

#### Device CPU average percentage (Decimal)

Entity: `macros.device_cpu_avg_perc`

Description: Returns the average CPU usage percentage across all processors on the device. Returns `0` when no processor data is available.

Source: `COALESCE((SELECT AVG(p.processor_usage) FROM processors AS p WHERE p.device_id = %devices.device_id), 0)`

#### Device component down [JunOS]

Entity: `macros.device_component_down_junos`

Description: Device component is down such as Fan, PSU, and so on for JunOS devices.

source. `sensors.sensor_class = "state" && sensors.sensor_current != "6" && (sensors.sensor_type = "jnxFruState" || sensors.sensor_type = "jnxFruTable") && sensors.sensor_current != "2" && sensors.sensor_alert = "1"`


#### Device component down [Cisco]

Entity: `macros.device_component_down_cisco`

Description: Device component is down such as Fan, PSU, and so on for Cisco devices.

Example: `sensors.sensor_current != "1" && sensors.sensor_current != "5" && sensors.sensor_type REGEXP "^cisco.*State$" && sensors.sensor_alert = "1"`


#### Device is up (Boolean)

Entity: `macros.device_up`

Description: Select only devices that are up.

Implies: macros.device

Source: `(devices.status = 1 AND macros.device)`

#### Device is down (Boolean)

Entity: `macros.device_down`

Description: Select only devices that are down.

Implies: macros.device

Source: `(devices.status = 0 AND macros.device)`

### ICMP

#### ICMP Latency Variance (Decimal)

Entity: `macros.ping_rtt_variance_perc`

Description: The percentage difference between the last ICMP latency and the rolling average.

Source: `((device_stats.ping_rtt_last - device_stats.ping_rtt_avg) \/ device_stats.ping_rtt_avg) * 100`

#### ICMP Packet Loss Variance (Decimal)

Entity: `macros.ping_loss_variance_perc`

Description: The percentage difference between the last ICMP packet loss and the rolling average.

Source: `((device_stats.ping_loss_last - device_stats.ping_loss_avg) \/ device_stats.ping_loss_avg) * 100`

### Time

#### Now (Datetime)

Entity: `macros.now`

Description: Alias of MySQL built-in `NOW()` function.

Source: `NOW()`

#### Past N Minutes (Datetime)

Entity: `macros.past_$m`

Description: Returns a MySQL Timestamp dated `$` Minutes in the
past. `$` can only be a supported Resolution.

Example: `macros.past_5m` is Last 5 Minutes.

Resolution: 5,10,15,30,60

Source: `DATE_SUB(NOW(),INTERVAL $ MINUTE)`

### Packet Loss

Entity: `(macros.packet_loss_5m)`

Description: Packet loss % value for the device within the last 5 minutes. **BROKEN**, only return 100 (down) or 0.
 
Example: `macros.packet_loss_5m` > 50

Entity: `(macros.packet_loss_15m)`

Description: Packet loss % value for the device within the last 15 minutes. **BROKEN**, only return 100 (down) or 0.

Example: `macros.packet_loss_15m` > 50

### Ports

### Port (Boolean)

Entity: `macros.port`

Description: Select only ports that are not deleted, ignored or disabled.

Source: `(ports.deleted = 0 AND ports.ignore = 0 AND ports.disabled = 0)`

### Port out error percent (Decimal)

Entity: `macros.port_out_error_perc`

Description: Return port out error percent.

Source: `((ports.ifOutErrors_rate / ports.ifOutUcastPkts_rate)*100)`

### Port in error percent (Decimal)

Entity: `macros.port_in_error_perc`

Description: Return port in error percent.

Source: `((ports.ifInErrors_rate / ports.ifInUcastPkts_rate)*100)`


#### Port is up (Boolean)

Entity: `macros.port_up`

Description: Select only ports that are up, and that must be up.

Implies: macros.port

Source: `(ports.ifOperStatus = up AND ports.ifAdminStatus = up AND macros.port)`

#### Port is down (Boolean)

Entity: `macros.port_down`

Description: Select only ports that are down.

Implies: macros.port

Source: `(ports.ifOperStatus != "up" AND ports.ifAdminStatus != "down" AND macros.port)`

#### Port-Usage in Percent (Decimal)

Entity: `macros.port_usage_perc`

Description: Return port-usage (max value of in and out) in percent.

Source: `((SELECT IF(ports.ifOutOctets_rate>ports.ifInOctets_rate,
ports.ifOutOctets_rate, ports.ifInOctets_rate)*8) /
ports.ifSpeed)*100`


#### Ports in usage perc (Int)

Entity: `macros.port_in_usage_perc`

Description: 

Source: `((ports.ifInOctets_rate*8) \/ ports.ifSpeed)*100`

#### Ports out usage perc (Int)

Entity: `((ports.ifOutOctets_rate*8)/ports.ifSpeed)*100`

Description: 

Source: `((ports.ifOutOctets_rate*8) \/ ports.ifSpeed)*100`


#### Port now down (Boolean)

Entity: `macros.port_now_down`

Description: Ports that were up before, and are now down.

Source: `ports.ifOperStatus != ports.ifOperStatus_prev && ports.ifOperStatus_prev = "up" && ports.ifAdminStatus = "up" && macros.port`

#### Port has xDP neighbour (Boolean)

Entity: `macros.port_has_xdp_neighbours`

Description: Ports that have an xDP (lldp, cdp, and so on) neighbour.

Source: `(macros.port && links.local_port_id = ports.port_id)`

#### Port has xDP neighbour already known in LibreNMS (Boolean)

Entity: `macros.port_has_xdp_neighbours_device`

Description: Ports that have an xDP (lldp, cdp, and so on) neighbour that is already known in LibreNMS.

Source: `(macros.port_has_xdp_neighbours && links.remote_port_id IS NOT NULL)`

### Sensors

#### Sensor (Boolean)

Entity: `macros.sensor`

Description: Select only sensors that are not ignored.

Source: `(sensors.sensor_alert = 1)`

#### Sensor Port Link (Boolean)

Entity: `macros.sensor_port_link`

Description: Selects only sensors that have a linked port, where the port is up and the device is up.

Source: `(sensors.entPhysicalIndex_measured = "port" AND sensors.entPhysicalIndex = ports.ifIndex AND macros.port_up AND macros.port_up)`

#### State Sensors critical (Boolean)

Entity: `macros.state_sensor_critical`

Description: Select only state sensors that are in a critical state.

Source: `(sensors.sensor_current = state_translations.state_value AND state_translations.state_generic_value = 2)`


#### State Sensors ok (Boolean)

Entity: `macros.state_sensor_ok`

Description: Select only state sensors that are in a ok state.

Source: `(sensors.sensor_current = state_translations.state_value AND state_translations.state_generic_value = 0)`

#### State Sensors unknown (Boolean)

Entity: `macros.state_sensor_unknown`

Description: Select only state sensors that are in a unknown state.

Source: `(sensors.sensor_current = state_translations.state_value AND state_translations.state_generic_value = 3)`

#### State Sensors warning (Boolean)

Entity: `macros.state_sensor_warning`

Description: Select only state sensors that are in a warning state.

Source: `(sensors.sensor_current = state_translations.state_value AND state_translations.state_generic_value = 1)`

### Misc

#### PDU over amperage [APC]

Entity: `macros.pdu_over_amperage_apc`

Description: APC PDU over amperage

Source: `sensors.sensor_class = "current" && sensors.sensor_descr = "Bank Total" && sensors.sensor_current > sensors.sensor_limit && devices.os = "apc"`

#### Service (Boolean)

Entity: `macros.service`

Description: Select only services that are not disabled or ignored.

Source: `(services.service_disabled = 0 && services.service_ignore = 0)`

### Custom Macros

Below are some examples of custom macros that you can add.

#### Sensor Delta Current (Decimal)

Entity: `macros.sensor_delta`

Description: Returns the delta of a sensor.

Source: `ABS(sensors.sensor_current - sensors.sensor_prev)`

### Sensor Change percent (Decimal)

Entity: `macros.sensor_change_perc`

Description: Returns the percent change of a sensor.

Source: `ABS((CAST(sensors.sensor_current as double) - sensors.sensor_prev)/sensors.sensor_current * 100)`
