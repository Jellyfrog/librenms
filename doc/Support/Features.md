# Features

This is a short list of supported features. Possibly, some are
missing. If you think that a feature is missing, ask us.

* Auto discovery
* Alerting
* Multiple environment sensors support
* Multiple protocols data collection (STP, OSPF, OSPFv3, BGP and so on)
* VLAN, ARP and FDB table collection
* Customizable Dashboards
* Device Backup integration (Oxidized, RANCID)
* Distributed Polling
* Multiple Authentication Methods (MySQL, LDAP, Active Directory, HTTP)
* NetFlow, sFlow, IPFIX (NfSen)
* Service monitoring (Nagios Plugins)
* Syslog (Integrated, Graylog)
* Traffic Billing (Quota, 95th Percentile)
* Two Factor Authentication
* API
* Auto Updating

## Supported Vendors

This is a list of supported vendors. Possibly, some are missing.
If you are not sure that your device is supported, ask us.

```sh exec="1"
grep -h "^text: " resources/definitions/os_detection/*.yaml \
| sed -E "s/^text: *[\"']?([^\"']+).*/\1/" \
| sort -f -u \
| awk '{\
  if (last != tolower(substr($0, 0, 1))) {\
    print "\n### "toupper(substr($0,0,1))"\n* "$0; last = tolower(substr($1, 0, 1))\
  } else {\
    print "* "$0\
  }\
}'
```
