## FreeBSD NFS Server

Superseded by the [generalized NFS support](NFS.md).

### SNMP Extend

1. Copy the shell script, fbsdnfsserver, to the desired host

```bash
wget https://github.com/librenms/librenms-agent/raw/master/snmp/fbsdnfsserver -O /etc/snmp/fbsdnfsserver
```

2. Make the script executable

```bash
chmod +x /etc/snmp/fbsdnfsserver
```

3. Edit your snmpd.conf file and add:

```bash
extend fbsdnfsserver /etc/snmp/fbsdnfsserver
```

4. Restart snmpd on your host

The system discovers the application automatically, as given at the top of
the page. If it does not, do the steps given under the `SNMP
Extend` heading at the top of the page.