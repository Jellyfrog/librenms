# mailcow-dockerized postfix

## SNMP Extend

1. Download the script into the desired host.

    ```bash
    wget https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/mailcow-dockerized-postfix -O /etc/snmp/mailcow-dockerized-postfix
    ```

2. Make the script executable

    ```bash
    chmod +x /etc/snmp/mailcow-dockerized-postfix
    ```
    > It is possible that you must install `pflogsumm` on a debian based OS. Make sure that the package is installed.

3. Edit your snmpd.conf file (usually /etc/snmp/snmpd.conf) and add:

    ```bash
    extend mailcow-postfix /etc/snmp/mailcow-dockerized-postfix
    ```

4. Restart snmpd on your host

    The system discovers the application automatically, as given at the top of
    the page. If it does not, do the steps given under the `SNMP
    Extend` heading at the top of the page.