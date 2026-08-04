# Mailscanner

### SNMP Extend

1. Download the script to the applicable host.
```
wget https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/mailscanner.php -O /etc/snmp/mailscanner.php
```

2. Make the script executable

    ```bash
    chmod +x /etc/snmp/mailscanner.php
    ```

3. Edit your snmpd.conf file (usually /etc/snmp/snmpd.conf) and add:

    ```bash
    extend mailscanner /etc/snmp/mailscanner.php
    ```

4. Restart snmpd on your host

    The system discovers the application automatically, as given at the top of
    the page. If it does not, do the steps given under the `SNMP
    Extend` heading at the top of the page.