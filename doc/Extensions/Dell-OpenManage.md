# Dell OpenManage Support

For Dell OpenManage support, you must install Dell OpenManage
(yes - really :)) (minimum 5.1) on the device that you want to
monitor. Make sure that net-snmp uses srvadmin. You see
something similar to:

```bash
master agentx
view all included .1
access notConfigGroup "" any noauth exact all none none
smuxpeer .1.3.6.1.4.1.674.10892.1
```

Restart net-snmp:

```bash
service snmpd restart
```

Make sure that srvadmin is started. To do this, you usually run:

```bash
/opt/dell/srvadmin/sbin/srvadmin-services.sh start
```

When this is done, add the device to LibreNMS as usual. Then you
start to receive Temperature and Fan speed data.

## Windows

Download OpenManage from the Dell support page
[Link](http://www.dell.com/support/contents/us/en/04/article/product-support/self-support-knowledgebase/enterprise-resource-center/systemsmanagement/OMSA)
and install OpenManage on your windows server. Make sure that [SNMP](../Support/SNMP-Configuration-Examples.md#windows-server-2012-r2-and-newer)
is set up and on, on your windows server.



