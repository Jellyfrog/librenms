To use Wireless Sensors on AsuswrtMerlin, an agent is necessary. The
agent operates on the client (AsuswrtMerlin) side. It makes sure
that the necessary Wireless Sensor information comes back for SNMP queries (from LibreNMS).

# Installation

## AsuswrtMerlin

Two items are necessary on the AsuswrtMerlin side - scripts that make the necessary information (for
SNMP replies), and an SNMP extend configuration update (to return the information for the applicable
query).

1: Install the scripts:

Copy the scripts from librenms-agent/snmp/Openwrt - the recommended location is /etc/librenms on AsuswrtMerlin (and add this
directory to /etc/sysupgrade.conf, to keep it through firmware updates).

The only file that you must edit is wlInterfaces.txt. It maps the wireless interfaces to
the display name that you want in LibreNMS. For example,
```
wlan0,wl-2.4G
wlan1,wl-5.0G
```

2: Update the AsuswrtMerlin SNMP configuration. Add extend support for the Wireless Sensor queries:

`vi /etc/config/snmpd`, and add the entries below (this applies when the scripts are installed in /etc/librenms and are executable).
Update the network interfaces to match the hardware,

```
config extend
        option name     interfaces
        option prog     "/bin/cat /etc/librenms/wlInterfaces.txt"
config extend
        option name     clients-wlan0
        option prog     "/etc/librenms/wlClients.sh wlan0"
config extend
        option name     clients-wlan1
        option prog     "/etc/librenms/wlClients.sh wlan1"
config extend
        option name     clients-wlan
        option prog     "/etc/librenms/wlClients.sh"
config extend
        option name     frequency-wlan0
        option prog     "/etc/librenms/wlFrequency.sh wlan0"
config extend
        option name     frequency-wlan1
        option prog     "/etc/librenms/wlFrequency.sh wlan1"
config extend
        option name     rate-tx-wlan0-min
        option prog     "/etc/librenms/wlRate.sh wlan0 tx min"
config extend
        option name     rate-tx-wlan0-avg
        option prog     "/etc/librenms/wlRate.sh wlan0 tx avg"
config extend
        option name     rate-tx-wlan0-max
        option prog     "/etc/librenms/wlRate.sh wlan0 tx max"
config extend
        option name     rate-tx-wlan1-min
        option prog     "/etc/librenms/wlRate.sh wlan1 tx min"
config extend
        option name     rate-tx-wlan1-avg
        option prog     "/etc/librenms/wlRate.sh wlan1 tx avg"
config extend
        option name     rate-tx-wlan1-max
        option prog     "/etc/librenms/wlRate.sh wlan1 tx max"
config extend
        option name     rate-rx-wlan0-min
        option prog     "/etc/librenms/wlRate.sh wlan0 rx min"
config extend
        option name     rate-rx-wlan0-avg
        option prog     "/etc/librenms/wlRate.sh wlan0 rx avg"
config extend
        option name     rate-rx-wlan0-max
        option prog     "/etc/librenms/wlRate.sh wlan0 rx max"
config extend
        option name     rate-rx-wlan1-min
        option prog     "/etc/librenms/wlRate.sh wlan1 rx min"
config extend
        option name     rate-rx-wlan1-avg
        option prog     "/etc/librenms/wlRate.sh wlan1 rx avg"
config extend
        option name     rate-rx-wlan1-max
        option prog     "/etc/librenms/wlRate.sh wlan1 rx max"
config extend
        option name     noise-floor-wlan0
        option prog     "/etc/librenms/wlNoiseFloor.sh wlan0"
config extend
        option name     noise-floor-wlan1
        option prog     "/etc/librenms/wlNoiseFloor.sh wlan1"
config extend
        option name     snr-wlan0-min
        option prog     "/etc/librenms/wlSNR.sh wlan0 min"
config extend
        option name     snr-wlan0-avg
        option prog     "/etc/librenms/wlSNR.sh wlan0 avg"
config extend
        option name     snr-wlan0-max
        option prog     "/etc/librenms/wlSNR.sh wlan0 max"
config extend
        option name     snr-wlan1-min
        option prog     "/etc/librenms/wlSNR.sh wlan1 min"
config extend
        option name     snr-wlan1-avg
        option prog     "/etc/librenms/wlSNR.sh wlan1 avg"
config extend
        option name     snr-wlan1-max
        option prog     "/etc/librenms/wlSNR.sh wlan1 max"
```

NOTE, to test each of the scripts above, run the applicable command.

NOTE, to examine the output data from one of these extensions, run this on the LibreNMS machine (for example),

`snmpwalk -v 2c -c public -Osqnv <openwrt-host> 'NET-SNMP-EXTEND-MIB::nsExtendOutputFull."frequency-wlan0"'`

NOTE, on the LibreNMS machine, make sure that snmp-mibs-downloader is installed.

NOTE, on the AsuswrtMerlin machine, make sure that distro is installed (that is, that the OS is correctly detected!).

3: Restart the snmp service on AsuswrtMerlin:

`service snmpd restart`

Then wait for discovery and polling on LibreNMS!
