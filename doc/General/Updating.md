# Updating an Install

By default, LibreNMS updates automatically one time each day at 00:19 hours.
If you disabled this feature, you can do a manual update.

## Manual update

To do a manual update, run this command as the `librenms` user:

```bash
./daily.sh
```

This updates the core LibreNMS files. It also updates the database
structure if updates are available.

## Advanced users

If you must do a manual update without `./daily.sh`, run these
commands:

```bash
cd /opt/librenms
git pull
rm bootstrap/cache/*.php
./scripts/composer_wrapper.php install --no-dev
./lnms migrate
./validate.php
```

## Disabling automatic updates

By default, LibreNMS does updates each day.
You can disable this in the WebUI:

!!! warning
    Do not remove daily.sh from the cronjob!
    It does database cleanup and other procedures, not only updates.

!!! setting "system/updates"
    ```bash
    lnms config:set update false
    ```

## Updating on set days

You can configure LibreNMS to do updates only on specified days. This
configuration is an array. By default, the array is empty.

!!! setting "system/updates"
    ```bash
    lnms config:get update_on_days
    ```
    ```bash
    lnms config:set update_on_days.+ "monday"
    ```
