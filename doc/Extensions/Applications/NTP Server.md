# NTP Server aka NTPD

SNMP extend script that gets stats from ntp server (ntpd).

### SNMP Extend

1. Download the script to the applicable host.

    ```bash
    wget https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/ntp-server.py -O /etc/snmp/ntp-server.py
    ```

2. Make the script executable

    ```bash
    chmod +x /etc/snmp/ntp-server.py
    ```

3. Edit your snmpd.conf file (usually `/etc/snmp/snmpd.conf`) and add:

    ```bash
    extend ntp-server /etc/snmp/ntp-server.py
    ```

4. Restart snmpd on your host

    ```bash
    sudo systemctl restart snmpd
    ```

    The system discovers the application automatically, as given at the top of the page. If it does not, do the steps given under the `SNMP Extend` heading at the top of the page.
