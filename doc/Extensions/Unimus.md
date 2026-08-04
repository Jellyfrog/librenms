# Unimus

The integration of LibreNMS with [Unimus](https://unimus.net) puts the config
view directly into LibreNMS: the latest backup, the full backup
history, and diffs between two backups are all available under
the Config tab of each device.

This integration needs a serviceable Unimus deployment
that already makes config backups of your devices. LibreNMS
reads backups through the Unimus API. It does not add or manage
devices in Unimus.

## Requirements

- Unimus with API v2 (Unimus 2.x or newer)
- An API token created in Unimus (`Settings -> User Management -> API tokens`).

## Configuration

Go to Unimus settings in the External Settings section of Global
Settings (`Settings -> Global Settings -> External -> Unimus
Integration`). Enable the integration, enter the URL of your Unimus
instance and enter your API token.

As an alternative, configure it from the CLI:

!!! setting "external/unimus"
    ```bash
    lnms config:set unimus.enabled true
    lnms config:set unimus.url http://127.0.0.1:8085
    lnms config:set unimus.token YOUR_API_TOKEN
    ```

When this is enabled, users with the show config permission see a
Config tab on devices that exist in Unimus.

## Device matching

LibreNMS matches a device to its Unimus equivalent by address.
It tries these values in order, until one matches:

1. The LibreNMS hostname
1. The hostname with the domain removed
1. The hostname with `mydomain` added at the end (if configured)
1. The device IP address

The system keeps successful matches in the cache for one hour. Thus, a device
added to Unimus a short time ago can take some minutes to appear.

## Notes

- When Unimus support is enabled, the Unimus Config tab
  replaces the Oxidized/RANCID Config tab.
- Binary backups show in the list, but LibreNMS cannot show their
  content. See those in Unimus itself.
- Unimus makes the diffs through its API. They show as a unified
  diff, with insertions, deletions and changes highlighted.
