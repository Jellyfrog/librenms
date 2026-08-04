## Mojo CAPE Submit

### SNMP Extend

This applies when `mojo_cape_submit` from `CAPE::Utils` is already configured.

1. Add the following to `snmpd.conf` and restarted SNMPD.

    ```bash
    extend mojo_cape_submit /usr/local/bin/mojo_cape_submit_extend
    ```

2. Restart snmpd on your host

    ```bash
    sudo systemctl restart snmpd
    ```

Then wait for the applicable machine to be discovered again, or enable it on the device settings app page.