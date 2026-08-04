## Suricata Extract

### SNMP Extend

1. Add the lines below to your snmpd config and restart. It is possible that you must
to be adjusted depending on where `suricata_extract_submit_extend` is
installed to.

    ```bash
    extend suricata_extract /usr/local/bin/suricata_extract_submit_extend
    ```

2. Restart snmpd on your system.

    ```bash
    sudo systemctl restart snmpd
    ```

    Then wait for the system to be discovered again, or enable it manually for the applicable server.
