# Supermicro

For some Supermicro information to show in LibreNMS, you must install an agent.

## Supermicro SuperDoctor
Install Supermicro SuperDoctor on the device that you want to monitor.

Then add this to /etc/snmp/snmpd.conf:

```bash
pass .1.3.6.1.4.1.10876 /usr/bin/sudo /opt/Supermicro/SuperDoctor5/libs/native/snmpagent
```

Restart net-snmp:

```bash
service snmpd restart
```
