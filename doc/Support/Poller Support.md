# Poller Support

This document tells you how to use `lnms device:poll` to debug
problems or to manually process data.

## Command options

```bash
Description:
  Poll data from device(s) as defined by discovery

Usage:
  device:poll [options] [--] <device spec>

Arguments:
  device spec            Device spec to poll: device_id, hostname, wildcard (*), odd, even, all

Options:
  -m, --modules=MODULES  Specify single module to be run. Comma separate modules, submodules may be added with /
  -x, --no-data          Do not update datastores (RRD, InfluxDB, etc)
  -h, --help             Display help for the given command. When no command is given display help for the list command
  -q, --quiet            Do not output any message
  -V, --version          Display this application version
      --ansi|--no-ansi   Force (or disable --no-ansi) ANSI output
  -n, --no-interaction   Do not ask any interactive question
      --env[=ENV]        The environment the command should run under
  -v|vv|vvv, --verbose   Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

## Poller Wrapper

We have a `poller-wrapper.py` script by [Job
Snijders](https://github.com/job). This script is the default at this
time.

To debug the output of poller-wrapper.py, you can add
`-d` to the end of the command. We do NOT recommend this in
cron.

## Poller config

These are the default poller config items. To disable a module
globally, set it to `false`. To disable it
for only one device, use the WebUI Device
-> Edit -> Modules.

!!! setting "poller/poller_modules"
    ```bash
    lnms config:set poller_modules.unix-agent false
    lnms config:set poller_modules.os true
    lnms config:set poller_modules.ipmi true
    lnms config:set poller_modules.sensors true
    lnms config:set poller_modules.processors true
    lnms config:set poller_modules.mempools true
    lnms config:set poller_modules.storage true
    lnms config:set poller_modules.netstats true
    lnms config:set poller_modules.hr-mib true
    lnms config:set poller_modules.ucd-mib true
    lnms config:set poller_modules.ipSystemStats true
    lnms config:set poller_modules.ports true
    lnms config:set poller_modules.nac false
    lnms config:set poller_modules.bgp-peers true
    lnms config:set poller_modules.junose-atm-vp false
    lnms config:set poller_modules.printer-supplies false
    lnms config:set poller_modules.ucd-diskio true
    lnms config:set poller_modules.wireless true
    lnms config:set poller_modules.ospf true
    lnms config:set poller_modules.ospfv3 true
    lnms config:set poller_modules.cisco-ipsec-flow-monitor false
    lnms config:set poller_modules.cisco-remote-access-monitor false
    lnms config:set poller_modules.cisco-cef false
    lnms config:set poller_modules.slas false
    lnms config:set poller_modules.mac-accounting true
    lnms config:set poller_modules.cipsec-tunnels false
    lnms config:set poller_modules.cisco-ace-loadbalancer false
    lnms config:set poller_modules.cisco-ace-serverfarms false
    lnms config:set poller_modules.cisco-cbqos false
    lnms config:set poller_modules.cisco-otv false
    lnms config:set poller_modules.cisco-vpdn false
    lnms config:set poller_modules.netscaler-vsvr false
    lnms config:set poller_modules.aruba-controller false
    lnms config:set poller_modules.entity-physical true
    lnms config:set poller_modules.entity-state false
    lnms config:set poller_modules.applications true
    lnms config:set poller_modules.availability true
    lnms config:set poller_modules.stp true
    lnms config:set poller_modules.vminfo false
    lnms config:set poller_modules.ntp true
    lnms config:set poller_modules.services true
    lnms config:set poller_modules.loadbalancers false
    lnms config:set poller_modules.mef false
    lnms config:set poller_modules.mef false
    ```

## OS based Poller config

To enable or disable modules for a specified OS, use
`lnms config:set os.<poller_module> false` OS based settings
have priority over global settings. Device based settings have priority
over all others.

You get small Poller performance improvements when you deactivate all
modules that a specified OS does not support.

E.g. to deactivate spanning tree, but activate the unix-agent module, for the linux OS

!!! setting "poller/poller_modules"
    ```bash
    lnms config:set os.linux.poller_modules.stp false
    lnms config:set os.linux.poller_modules.unix-agent true
    ```

## Poller modules

`unix-agent`: Enable the check_mk agent for external support for applications.

`system`: Provides information on some common items like uptime, sysDescr and sysContact.

`os`: Os detection. This module finds the OS of the device.

`ipmi`: Enables support for IPMI if login details have been provided for IPMI.

`sensors`: Sensor detection such as Temperature, Humidity, Voltages + More.

`processors`: Processor support for devices.

`mempools`: Memory detection support for devices.

`storage`: Storage detection for hard disks

`netstats`: Statistics for IP, TCP, UDP, ICMP and SNMP.

`hr-mib`: Host resource support.

`ucd-mib`: Support for CPU, Memory and Load.

`ipSystemStats`: IP statistics for device.

`ports`: This module finds all ports on a device, but not the ports
that the config options set to ignored.

`xdsl`: This module collects more metrics for xdsl interfaces.

`nac`: Network Access Control (NAC) or 802.1X support.

`bgp-peers`: BGP detection and support.

`junose-atm-vp`: Juniper ATM support.

`printer-supplies`: Toner levels support.

`ucd-diskio`: Disk I/O support.

`wifi`: WiFi Support for those devices with support.

`ospf`: OSPF Support.

`ospfv3`: OSPFv3 Support.

`cisco-ipsec-flow-monitor`: IPSec statistics support.

`cisco-remote-access-monitor`: Cisco remote access support.

`cisco-cef`: CEF detection and support.

`slas`: SLA detection and support.

`mac-accounting`: MAC Address account support.

`cipsec-tunnels`: IPSec tunnel support.

`cisco-ace-loadbalancer`: Cisco ACE Support.

`cisco-ace-serverfarms`: Cisco ACE Support.

`netscaler-vsvr`: Netscaler support.

`aruba-controller`: Aruba wireless controller support.

`entity-physical`: Module that finds the device hardware.

`applications`: Device application support.

`availability`: Device Availability Calculation.

## Running

These are examples of how to run the poller from your installation directory.

```bash
lnms device:poll localhost

lnms device:poll localhost -m ports
```

## Debugging

To get debug output, run the poller process
with the `-vv` flag. You can do this for
all modules, for one module, or for multiple modules:

All Modules

```bash
lnms device:poll localhost -vv
```

Single Module

```bash
lnms device:poll localhost -m ports -vv
```

Multiple Modules

```bash
lnms device:poll localhost -m ports,entity-physical -vv
```

`-vv` does not usually show much sensitive information, but `-vvv`
does. Thus, clean the output before you paste it in a public
location. The debug output contains snmp details and other items,
which include port descriptions.

The output contains:

DB Updates

RRD Updates

SNMP Response
