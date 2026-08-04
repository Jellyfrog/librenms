## Pi-hole

Pi-hole is a DNS server that can be used to block ads and other
unwanted content.  This script reports the status of Pi-hole.

### SNMP Extend

1. Copy the shell script, pi-hole, to the desired host.

    ```bash
    wget https://github.com/librenms/librenms-agent/raw/master/snmp/pi-hole -O /etc/snmp/pi-hole
    ```

2. Make the script executable

    ```bash
    chmod +x /etc/snmp/pi-hole
    ```

3. Edit your snmpd.conf file and add:

    ```bash
    extend pi-hole /etc/snmp/pi-hole
    ```

4. To get all data you must get your `API auth token` from Pi-hole
server and change the `API_AUTH_KEY` entry inside the snmp script.

5. Restard snmpd.

    ```bash
    sudo systemctl restart snmpd
    ```

    The system discovers the application automatically, as given at the top of
    the page. If it does not, do the steps given under the `SNMP
    Extend` heading at the top of the page.