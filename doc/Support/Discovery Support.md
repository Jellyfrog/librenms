# Discovery Support

This document tells you how to use discovery to debug problems or
to manually process data.

The basic command to start is:

`lnms device:discover HOSTNAME`

## Command options

```bash
Description:
  Discover information about existing devices, defines what will be polled

Usage:
  device:discover [options] [--] <device spec>

Arguments:
  device spec            Device spec to discover: device_id, hostname, wildcard (*), odd, even, all

Options:
  -m, --module=MODULE   Specify module(s) to be run. submodules may be added with /.  Multiple values allowed. (multiple values allowed)
  -h, --help            Display help for the given command. When no command is given display help for the list command
      --silent          Do not output any message
  -q, --quiet           Only errors are displayed. All other output is suppressed
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
      --env[=ENV]       The environment the command should run under
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

`<device spec>` Use this to specify a device by id or hostname (a
wildcard with * is possible). You can also specify odd and even. all runs
discovery on all devices. new polls only the devices
that were added a short time ago, or that are selected for rediscovery.

`-v` Enables debug output. With this, you can see what occurs during
a discovery run.

`-vv` Enables verbose debug output. This includes items such as SQL queries
and responses from snmp, with sensitive data masked as much as possible.

`-vvv` Enables full debug output with all data unchanged.

`-m` With this, you can specify the module that you want to run for discovery.

## Discovery wrapper

We have a `discovery-wrapper.py` script. It is based on
`poller-wrapper.py` by [Job Snijders](https://github.com/job). This
script is the default at this time.

To debug the output of discovery-wrapper.py, you can
add `-d` to the end of the command. We do NOT recommend this
in cron.

You can also use `-m` to give a list of comma-separated modules.
Refer to [Command options](#command-options) of `lnms device:discover -h`.
Example: `/opt/librenms/discovery-wrapper.py 1 -m bgp-peers`

If you want to change back to `lnms device:discover` (not recommended), you can replace:

`33  */6   * * *   librenms    /opt/librenms/discovery-wrapper.py 1 >> /dev/null 2>&1`

With:

`33  */6   * * *   librenms    /opt/librenms/lnms device:discover all >> /dev/null 2>&1`

## Discovery config

These are the default discovery config items. To disable a module
globally, set it to 0. To disable it for only one
device, use the WebUI -> Device -> Settings ->
Modules.

!!! setting "discovery/discovery_modules"
    ```bash
    lnms config:set discovery_modules.os true
    lnms config:set discovery_modules.ports true
    lnms config:set discovery_modules.ports-stack true
    lnms config:set discovery_modules.entity-physical true
    lnms config:set discovery_modules.entity-state false
    lnms config:set discovery_modules.processors true
    lnms config:set discovery_modules.mempools true
    lnms config:set discovery_modules.cisco-vrf-lite true
    lnms config:set discovery_modules.mac-accounting true
    lnms config:set discovery_modules.cisco-pw false
    lnms config:set discovery_modules.vrf false
    lnms config:set discovery_modules.cisco-cef false
    lnms config:set discovery_modules.slas false
    lnms config:set discovery_modules.cisco-otv false
    lnms config:set discovery_modules.ipv4-addresses true
    lnms config:set discovery_modules.ipv6-addresses true
    lnms config:set discovery_modules.route false
    lnms config:set discovery_modules.sensors true
    lnms config:set discovery_modules.storage true
    lnms config:set discovery_modules.hr-device true
    lnms config:set discovery_modules.discovery-protocols true
    lnms config:set discovery_modules.arp-table true
    lnms config:set discovery_modules.discovery-arp false
    lnms config:set discovery_modules.junose-atm-vp false
    lnms config:set discovery_modules.bgp-peers true
    lnms config:set discovery_modules.vlans true
    lnms config:set discovery_modules.vminfo false
    lnms config:set discovery_modules.printer-supplies false
    lnms config:set discovery_modules.ucd-diskio true
    lnms config:set discovery_modules.applications false
    lnms config:set discovery_modules.services true
    lnms config:set discovery_modules.stp true
    lnms config:set discovery_modules.ntp true
    lnms config:set discovery_modules.loadbalancers false
    lnms config:set discovery_modules.mef false
    lnms config:set discovery_modules.wireless true
    lnms config:set discovery_modules.fdb-table true
    lnms config:set discovery_modules.xdsl false
    ```

## OS based Discovery config

To enable or disable modules for a specified OS, use
`lnms config:set`. OS based settings have priority
over global settings. Device based settings have priority over all others

You get better discovery performance when you deactivate all
modules that a specified OS does not support.

For example, to deactivate spanning tree, but activate the discovery-arp module, for the linux OS

!!! setting "discovery/discovery_modules"
    ```bash
    lnms config:set os.linux.discovery_modules.stp false
    lnms config:set os.linux.discovery_modules.discovery-arp true
    ```

## Discovery modules

`os`: Os detection. This module finds the OS of the device.

`ports`: This module finds all ports on a device, but not the ports
that the config options set to ignored.

`ports-stack`: Same as ports except for stacks.

`xdsl`: Module to collect more metrics for xDSL interfaces.

`entity-physical`: Module that finds the device hardware.

`processors`: Processor support for devices.

`mempools`: Memory detection support for devices.

`cisco-vrf-lite`: VRF-Lite detection and support.

`ipv4-addresses`: IPv4 Address detection

`ipv6-addresses`: IPv6 Address detection

`route`: This module loads the routing table of the device. The default route
 limit is 1000 (configurable with `lnms config:set routes.max_number 1000`), with history data.

`sensors`: Sensor detection such as Temperature, Humidity, Voltages + More

`storage`: Storage detection for hard disks

`hr-device`: Processor and Memory support through HOST-RESOURCES-MIB.

`discovery-protocols`: Auto discovery module for xDP, OSPF, OSPFv3 and BGP.

`arp-table`: Detection of the ARP table for the device.

`fdb-table`: Detection of the Forwarding DataBase table for the
device, with history data.

`discovery-arp`: Auto discovery through ARP.

`junose-atm-vp`: Juniper ATM support.

`bgp-peers`: BGP detection and support.

`vlans`: VLAN detection and support.

`mac-accounting`: MAC Address account support.

`cisco-pw`: Pseudowires wires detection and support.

`vrf`: VRF detection and support.

`cisco-cef`: CEF detection and support.

`slas`: SLA detection and support.

`vminfo`: Detection of vm guests for VMware ESXi, libvert and XCP-NG

`printer-supplies`: Toner levels support.

`ucd-diskio`: Disk I/O support.

`services`: *Nix services support.

## Running

These are examples of how to run discovery from your installation directory.

```bash
lnms device:discover localhost

lnms device:discover localhost -m ports
```

## Debugging

To get debug output, run the discovery process
with the `-v` flag. You can do this for all modules, for one module,
or for multiple modules:

All Modules

```bash
lnms device:discover localhost -vv
```

Single Module

```bash
lnms device:discover localhost -m ports -vv
```

Multiple Modules

```bash
lnms device:discover localhost -m ports,entity-physical -vv
```

`-vv` does not usually show much sensitive information, but `-vvv`
does. Thus, clean the output before you paste it in a public
location. The debug output contains snmp details and other items,
which include port descriptions.

The output contains:

- DB Updates
- SNMP Response
