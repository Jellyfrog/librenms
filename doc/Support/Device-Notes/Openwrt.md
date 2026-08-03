To use Wireless Sensors on Openwrt, an agent is necessary. The
agent operates on the client (Openwrt) side. It makes sure
that the necessary Wireless Sensor information comes back for SNMP queries (from LibreNMS).

# Installation

## Openwrt

Two items are necessary on the Openwrt side - scripts that make the necessary information (for
SNMP replies), and an SNMP extend configuration update (to return the information for the applicable
query).

1: Install the scripts:

Copy the scripts from librenms-agent repository - the recommended location is /etc/librenms on Openwrt (and add this
directory to /etc/sysupgrade.conf, to keep it through firmware updates):
```
wget -O /etc/librenms/wlClients.sh https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/Openwrt/wlClients.sh
wget -O /etc/librenms/wlFrequency.sh https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/Openwrt/wlFrequency.sh
wget -O /etc/librenms/wlInterfaces.txt https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/Openwrt/wlInterfaces.txt
wget -O /etc/librenms/wlNoiseFloor.sh https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/Openwrt/wlNoiseFloor.sh
wget -O /etc/librenms/wlRate.sh https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/Openwrt/wlRate.sh
wget -O /etc/librenms/wlSNR.sh https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/Openwrt/wlSNR.sh
wget -O /etc/librenms/distro https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/distro
chmod +x /etc/librenms/*.sh
chmod +x /etc/librenms/distro
```

The only file that you must edit is wlInterfaces.txt. It maps the wireless interfaces to
the display name that you want in LibreNMS. For example,
```
wlan0,wl-2.4G
wlan1,wl-5.0G
```

2: Update the Openwrt SNMP configuration. Add extend support for the OS detection and the Wireless Sensor queries:

`vi /etc/config/snmpd`, and add the entries below (this applies when the scripts are installed in /etc/librenms and are executable).
Update the network interfaces to match the hardware,

```
config extend
        option name	distro
        option prog	'/etc/librenms/distro'
config extend
        option name	hardware
        option prog	'/bin/cat'
        option args	'/sys/firmware/devicetree/base/model'
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

NOTE, on the AsuswrtMerlin machine, make sure that distro is installed (i.e. that the OS is correctly detected!).

3: Restart the snmp service on Openwrt:

`service snmpd restart`

Then wait for discovery and polling on LibreNMS!
