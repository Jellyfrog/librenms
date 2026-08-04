# Fast up/down checking

Normally, LibreNMS sends an ICMP ping to the device before polling to
check if it is up or down. This check is tied to the poller frequency,
which is usually 5 minutes. This means that it can take up to 5 minutes
to find out if a device is down.

Some users want to know if devices stop replies to ping more
quickly than that. LibreNMS offers a `ping.php` script to run ping
checks as quickly as possible without increasing snmp load on your
devices by switching to 1 minute polling.

!!! warning

    You likely want to have a device down alert rule to take advantage
    of Fast Ping checks. You can find one in the [Alert Rules
    Collection](../Alerting/Rules.md#alert-rules-collection).

## Setting the ping check to 1 minute

To use the dispatcher service to run the fast pings:

!!! setting "poller/rrdtool"

    ```bash
    lnms config:set schedule_type.ping dispatcher
    lnms config:set service_ping_frequency 60
    systemctl restart librenms.service
    ```

If you are still using CRON:

```title="/etc/cron.d/librenms"
*    *    * * *   librenms    /opt/librenms/ping.php >> /dev/null 2>&1
```

!!! note

    If you are using distributed pollers you can restrict a
    poller to a group by appending `-g` to the cron entry. Alternatively,
    run `ping.php` only on a single node.

## Device dependencies

The `ping.php` script respects device dependencies, but the main poller
does not (for technical reasons). However, using this script does not
disable the icmp check in the poller, and the system can report a child as
down before the parent.

## Settings

`ping.php` uses much the same settings as the poller fping with one
exception: retries is used instead of count.
`ping.php` does not measure loss and avg response time, only up/down, so
when a device replies, it stops the pings to it.

!!! setting "poller/ping"

    ```bash
    lnms config:set fping_options.retries 2
    lnms config:set fping_options.timeout 500
    lnms config:set fping_options.interval 500
    ```
