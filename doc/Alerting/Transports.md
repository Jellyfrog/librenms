# Transports

Transports are in `LibreNMS/Alert/Transport/`. You can
configure them in the WebUI under Alerts -> Alert Transports.

The system collects Contacts (email addresses) automatically and gives
them to the configured transports. By default, the system collects the
Contacts only when the alert starts, and it ignores subsequent changes
in contacts for the incident. If you want the system to collect the
contacts again before each dispatch, set:

!!! setting "alerting/general"
    ```bash
    lnms config:set alert.fixed-contacts false
    ```

The contacts always include the `SysContact` set in the
Device's SNMP configuration, and also each LibreNMS user that has
a minimum of `read` permissions on the entity that gets the alert.

At this time, LibreNMS supports only Port or Device permissions.

## Using a Proxy

[Proxy Configuration](../Support/Configuration.md#proxy-support)

## Using a AMQP based Transport

You must install one more php module : `bcmath`
