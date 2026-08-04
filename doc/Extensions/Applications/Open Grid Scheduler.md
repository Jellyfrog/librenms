## Open Grid Scheduler

Shell script to track the OGS/GE jobs running on clusters.

### SNMP Extend

1. Download the script to the applicable host.

    ```bash
    wget https://raw.githubusercontent.com/librenms/librenms-agent/master/agent-local/rocks.sh -O /etc/snmp/rocks.sh
    ```

2. Make the script executable

    ```bash
    chmod +x /etc/snmp/rocks.sh
    ```

3. Edit your snmpd.conf file (usually `/etc/snmp/snmpd.conf`) and add:

    ```bash
    extend ogs /etc/snmp/rocks.sh
    ```

4. Restart snmpd.

    The system discovers the application automatically, as given at the top of
    the page. If it does not, do the steps given under the `SNMP
    Extend` heading at the top of the page.