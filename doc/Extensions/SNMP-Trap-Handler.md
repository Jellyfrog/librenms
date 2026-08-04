# SNMP trap handling

Currently, LibreNMS supports a lot of trap handlers. You can check them on
GitHub [here](https://github.com/librenms/librenms/tree/master/LibreNMS/Snmptrap/Handlers).
To add more, refer to [Adding new SNMP Trap handlers](../Developing/SNMP-Traps.md). snmptrapd handles the traps.

snmptrapd is an SNMP application that receives and logs SNMP TRAP and INFORM messages.
> The default is to listen on UDP port 162 on all IPv4 interfaces. Since 162 is a
privileged port, snmptrapd must typically be run as root.

## Configure snmptrapd

Install snmptrapd with your package manager.

For example (Debian based systems):

```
sudo apt install snmptrapd -y
```

In `/etc/snmp/snmptrapd.conf`, add :

```text
disableAuthorization yes
authCommunity log,execute,net COMMUNITYSTRING
traphandle default /opt/librenms/snmptrap.php
```

For snmptrapd to parse traps correctly, we must add MIBs to the service.

### Option 1

Make the folder `/etc/systemd/system/snmptrapd.service.d/` and edit
the file `/etc/systemd/system/snmptrapd.service.d/mibs.conf` and add
the following content.

You can adjust it to add vendor directories
for devices you care about. In the example below, standard and cisco
directories are defined, and only IF-MIB is loaded.

```ini
[Service]
Environment=MIBDIRS=+/opt/librenms/mibs:/opt/librenms/mibs/cisco
Environment=MIBS=+IF-MIB
```

For non-systemd systems, you can edit TRAPDOPTS in the init script in /etc/init.d/snmptrapd.

`TRAPDOPTS="-Lsd  -M /opt/librenms/mibs -m IF-MIB -f -p $TRAPD_PID"`

Along with any necessary configuration to receive the traps from your
devices (community, and so on)


### Option 2
> Tested on Ubuntu 18

Set up your service like:

```
[Unit]
Description=Simple Network Management Protocol (SNMP) Trap Daemon.
After=network.target
ConditionPathExists=/etc/snmp/snmptrapd.conf

[Service]
Environment="MIBSDIR=/opt/librenms/mibs"
Type=simple
ExecStart=/usr/sbin/snmptrapd -f -m IF-MIB -M /opt/librenms/mibs
ExecReload=/bin/kill -HUP $MAINPID

[Install]
WantedBy=multi-user.target
```
> In Ubuntu 18 is service located by default in ```/etc/systemd/system/multi-user.target.wants/snmptrapd.service```

Here is a list of snmptrapd options:

| Option | Description                                                                                      |
| -------| ------------------------------------------------------------------------------------------------ |
|   -a   | Ignore authenticationFailure traps. [OPTIONAL]                                                   |
|   -f   | Do not fork from the shell                                                                       |
|   -n   | Use numeric addresses instead of attempting hostname lookups (no DNS) [OPTIONAL]                 |
|   -m   | MIBLIST: use MIBLIST (`FILE1-MIB:FILE2-MIB`). `ALL` = Load all MIBS in DIRLIST. (usually fails) |
|   -M   | DIRLIST: use DIRLIST as the list of locations to look for MIBs. Option is not recursive, so you need to specify each DIR individually, separated by `:`. (For example: /opt/librenms/mibs:/opt/librenms/mibs/cisco:/opt/librenms/mibs/edgecos)|

A good procedure is to not use `-m ALL`, because then it tries to load all the MIBs in DIRLIST. This
usually fails (snmptrapd cannot load that many mibs). It is better to specify the
exact MIB files defining the traps you are interested in, for example for LinkDown and LinkUp
as well as BGP traps, use `-m IF-MIB:BGP4-MIB`. Multiple files can be added, separated with `:`.

If you want to test or store original TRAPS in log then:

Create a folder for storing traps for example in file `traps.log`

```
sudo mkdir /var/log/snmptrap

```

Add the following config to your snmptrapd.service after `ExecStart=/usr/sbin/snmptrapd -f -m ALL -M /opt/librenms/mibs`

```
-tLf /var/log/snmptrap/traps.log

```

On SELinux, you need to configure SELinux for SNMPd to communicate to LibreNMS:

```
cat > snmptrap.te << EOF
module snmptrap 1.0;

require {
        type httpd_sys_rw_content_t;
        type snmpd_t;
        class file { append getattr open read };
        class capability dac_override;
}

#============= snmpd_t ==============

allow snmpd_t httpd_sys_rw_content_t:file { append getattr open read };
allow snmpd_t self:capability dac_override;
EOF
checkmodule -M -m -o snmptrap.mod snmptrap.te
semodule_package -o snmptrap.pp -m snmptrap.mod
semodule -i snmptrap.pp
```

After successfully configuring the service, reload service files, enable, and start the snmptrapd service:

```
sudo systemctl daemon-reload
sudo systemctl enable snmptrapd
sudo systemctl restart snmptrapd
```

## Testing

The easiest test is to generate a trap from your device. Usually, changing the configuration on a network device, or
when you connect/disconnect a network cable (LinkUp, LinkDown), this makes a trap. You can make sure of it with `tcpdump`, `tshark` or `wireshark`.

You can also generate a trap using the `snmptrap` command from the LibreNMS server itself (if and only if the LibreNMS server is monitored).

### How to send SNMP v2 Trap

The command below takes the form of:

```
snmptrap -v <snmp_version> -c <community> <destination_host> <uptime> <OID_or_MIB> <object> <value_type> <value>
```

Using OID's:

```
snmptrap -v 2c -c public localhost '' 1.3.6.1.4.1.8072.2.3.0.1 1.3.6.1.4.1.8072.2.3.2.1 i 123456
```

If you configured the log of traps to ```/var/log/snmptrap/traps.log```, you see a new entry in `traps.log`:

```
2020-03-09 16:22:59 localhost [UDP: [127.0.0.1]:58942->[127.0.0.1]:162]:
SNMPv2-MIB::sysUpTime.0 = Timeticks: (149721964) 17 days, 7:53:39.64    SNMPv2-MIB::snmpTrapOID.0 = OID: SNMPv2-SMI::enterprises.8072.2.3.0.1   SNMPv2-SMI::enterprises.8072.2.3.2.1 = INTEGER: 123456
```

and in LibreNMS your localhost device eventlog like:

```
2020-03-09 16:22:59             SNMP trap received: SNMPv2-SMI::enterprises.8072.2.3.0.1
```

### Why we need Uptime

When you send a trap, it must obey a set of standards. Each trap needs an uptime value. Uptime is the time since the system boot. Sometimes this is the operating system. Other devices possibly use the SNMP engine uptime. In each condition, a value is sent.

Thus, which value must you type in the commands below? When you supply no value, with two single quotes '', the command gets the value from the operating system on which you run this.

### Event logging

You can configure generic event logs for snmp traps.  This logs
an event of the type trap for received traps. These events can be used for alerting.
By default, only the TrapOID is logged. But you can enable the "detailed" variant,
and the system logs all the data received with the trap.

The parameter can be found in General Settings / External / SNMP Traps Integration.

It can also be configured in your config.

!!! setting "external/snmptrapd"
    ```bash
    lnms config:set snmptraps.eventlog 'unhandled'
    lnms config:set snmptraps.eventlog_detailed false
    ```

Valid options are:

- `unhandled` the system logs only unhandled traps (default value)
- `all` log all traps
- `none` no traps create a generic event log (handled traps can continue to log events)
