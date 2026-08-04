## PowerDNS

An authoritative DNS server: <https://www.powerdns.com/auth.html>

=== "SNMP Extend"

    1. Copy the shell script, powerdns.py, to the desired host
    
    ```bash
    wget https://github.com/librenms/librenms-agent/raw/master/snmp/powerdns.py -O /etc/snmp/powerdns.py
    ```

    2. Make the script executable
    
    ```bash
    chmod +x /etc/snmp/powerdns.py
    ```

    3. Edit your snmpd.conf file and add:

    ```bash
    extend powerdns /etc/snmp/powerdns.py
    ```

    4. Restart snmpd on your host

    The system discovers the application automatically, as given at the top of
    the page. If it does not, do the steps given under the `SNMP
    Extend` heading at the top of the page.

=== "Agent"

    [Install the agent](../Agent-Setup.md) on this device, if it is not already

    and copy the `powerdns` script to `/usr/lib/check_mk_agent/local/`

=== "Permissions"

   If snmpd runs as a user without privileges, it is possible that you must use sudo.
   Here is a rough outline of one way to accomplish this.

   Add `Debian-snmp ALL=(ALL) NOPASSWD: /usr/bin/pdns_control list` to your sudoers file
   
   In powerdns.py, modify the process from `[pdnscontrol, "list"]` to `["/usr/bin/sudo", pdnscontrol, "list"]`
   
