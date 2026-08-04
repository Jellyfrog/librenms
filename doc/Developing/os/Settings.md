# Optional OS Settings

This page documents settings that you can set in the os yaml files or
in config.php. All settings here are optional. If they are not
set, the system uses the global default.

### User override in config.php

Users can replace these settings in their config.php.

For example, to set an alternate icon for ios:

```php
$config['os']['ios']['icon'] = 'fuzzybunny';
```

### Ignoring Sensors

You can remove some sensors in the configuration:

- Remove all 'current' sensors for the Operating System 'vrp'.

```php
$config['os']['vrp']['disabled_sensors']['current'] = true;
```

- Remove all sensors with a description that matches the regexp ```'/PEM Iout/'``` for the Operating System iosxe.

```php
$config['os']['iosxe']['disabled_sensors_regex'][] = '/PEM Iout/';
```

- Remove all 'power' sensors with a description that matches the regexp ```'/ Power [TR]x /'``` for the Operating System iosxr.

```php
$config['os']['iosxr']['disabled_sensors_regex']['power'][] = '/ Power [TR]x /';
```

- Ignore all temperature sensors

```php
$config['disabled_sensors']['temperature'] = true;
```

- Remove all sensors with a description that matches the regexp ```'/PEM Iout/'```.

```php
$config['disabled_sensors_regex'][] = '/PEM Iout/';
```

### Ignoring Interfaces

See also: [Global Ignoring Interfaces Config](../../Support/Configuration.md#interfaces-to-be-ignored)

> The system merges these settings with the global settings. Thus, you can
> cancel global settings only with good_if

```yaml
empty_ifdescr: false # allow empty ifDescr
bad_if: # ifDescr (substring, case insensitive)
    - lp0
bad_if_regexp: # ifDescr (regex, case insensitive)
    - "/^ng[0-9]+$/"
bad_ifname_regexp: # ifName (regex, case insensitive)
    - "/^xdsl_channel /"
bad_ifalias_regexp: # ifAlias (regex, case insensitive)
    - "/^vlan/"
bad_iftype: # ifType (substring)
    - sonet
good_if: # ignore all other bad_if settings ifDescr (substring, case insensitive)
    - virtual
bad_ifoperstatus # IfOperStatus (substring, case insensitive)
    - notPresent
```

### Controlling interface labels

By default, we use ifDescr as the label for ports/interfaces.
If you set `ifname` or `ifalias`, that replaces it.  Set only one
of these.  ifAlias comes from the user. `ifindex` adds the ifindex
to the end of the port label.

```yaml
ifname: true
ifalias: true

ifindex: true
```

### Poller and Discovery Modules

You can enable or disable the discovery and poller modules
for each OS.  The default values are usually good. Thus, you usually
change only a small number. You can enable or disable these modules
for each device in the webui, and for each os or globally in config.php. Usually,
a poller module does not operate if its related discovery module
is not enabled.

Do not set these to false in the OS definitions, unless the module has a
large negative effect on polling.  When you set modules in the definition,
users have less control of the modules.

```yaml
poller_modules:
    bgp-peers: true
discovery_modules:
    arp-table: false
```

### SNMP Settings

#### Disable snmpbulkwalk

Some devices have snmp implementations with bugs. They do not reply well to
the more efficient snmpbulkwalk. To disable snmpbulkwalk, and use only
snmpwalk for an OS, set this.

```yaml
snmp_bulk: false
```

If only some OIDs fail with snmpbulkwalk, you can disable only those OIDs.
This must match exactly the OID that LibreNMS walks. MIB::oid is recommended to prevent name collisions.

```yaml
oids:
    no_bulk:
        - UCD-SNMP-MIB::laLoadInt
```

#### Limit the oids per snmpget

```yaml
snmp_max_oid: 8
```
#### Define SNMP repeater value by OS

Example ios:

```
lnms config:set ios.snmp.max_repeaters: 30
```

### Storage Settings

See also: [Global Storage Config](../../Support/Configuration.md#storage-configuration)

```yaml
ignore_mount_array: # exact match
    - /var/run
ignore_mount_string: # substring
    - run
ignore_mount_regexp: # regex
    - "/^\/var/"
```
