# Cleanup Options

When the number of devices in your LibreNMS installation increases,
items such as the RRD files and the MySQL database (which contains
eventlogs, Syslogs, performance data etc.) also increase. Your
LibreNMS installation can become large. Thus, it is necessary to
remove old entries. With Cleanup Options, you have control of this.

These options operate only if ```daily.sh``` runs from cron, as given in the installation instructions.

!!! setting "system/cleanup"
    ```bash
    lnms config:set eventlog_purge 30
    lnms config:set syslog_purge 30
    lnms config:set route_purge 10
    lnms config:set alert_log_purge 365
    lnms config:set authlog_purge 30
    lnms config:set ports_fdb_purge 10
    lnms config:set ports_nac_purge 10
    lnms config:set rrd_purge 0
    lnms config:set ports_purge true
    lnms config:set networks_purge true
    ```

With these options, the system automatically removes LibreNMS data
that is more than X days old. You can change each option. The values
are in days.

**NOTE**: `rrd_purge` is NOT set by default. This option
automatically removes all RRD files that did not get updates for the
set number of days. Enable this option only if you agree with this
behavior. (All active RRD files get updates in each polling period.)

!!! note
    `rrd_purge` does not operate through rrdcached. The rrd folder must be accessible through the local file system or a remote file share.
    This is the same for docker and Kubernetes.

## Ports Purge

When you add devices, some interfaces must be removed later, because
they are set to ignored, or are bad interfaces, or are marked as
deleted.

You can remove all deleted ports from the WebUI (see below), or set
`lnms config:set ports_purge true`.

In the Web UI, under the Ports Tab in the Nav Bar, click "Deleted".
Then click "Purge all deleted". This removes all the ports.

## Networks Purge

If you add and remove subnets, the database can contain subnets that
have no IP addresses attached to them. If you enable the
networks_purge option, the system removes these unused networks from
the database.
