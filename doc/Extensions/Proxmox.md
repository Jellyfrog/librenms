# Proxmox graphing

You can create graphs of the Proxmox **VMs** that run on
your monitored machines. At this time, the system creates only traffic
graphs: one for each interface on each VM. Possibly, IO graphs
come later.

The final goal: create traffic bills for VMs,
independently of the physical machine on which the VM runs.

## Enabling Proxmox graphs

To enable Proxmox graphs, do the following:

In config.php, enable Proxmox:

```php
$config['enable_proxmox'] = 1;
```

Then, install git and
[librenms-agent](Applications.md) on
the machines running Proxmox and enable the Proxmox-script using:

```bash
cp /opt/librenms-agent/agent-local/proxmox /usr/lib/check_mk_agent/local/proxmox
chmod +x /usr/lib/check_mk_agent/local/proxmox
```

Then, enable and start the check_mk service with systemd

```bash
cp /opt/librenms-agent/check_mk@.service /opt/librenms-agent/check_mk.socket /etc/systemd/system
systemctl daemon-reload
systemctl enable check_mk.socket && systemctl start check_mk.socket
```

Then, in LibreNMS, make the librenms-agent and proxmox application
flag active for the device that you monitor. You now see an
application in LibreNMS, and also a new menu-item in the topmenu.
With it, you can select the cluster that you want to see.

## Note, if you want to use use xinetd instead of systemd

You can use the librenms-agent started by xinetd, not
systemd. One use case: you must use an old Proxmox
installation. After you install the librenms-agent (see above),
copy and enable the xinetd config. Then restart the xinetd service:

```bash
cp check_mk_xinetd /etc/xinetd.d/check_mk
/etc/init.d/xinetd restart
```
