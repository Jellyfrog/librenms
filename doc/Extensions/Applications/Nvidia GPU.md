## Nvidia GPU

### SNMP Extend

1. Copy the shell script, nvidia, to the desired host

    ```bash
    wget https://github.com/librenms/librenms-agent/raw/master/snmp/nvidia -O /etc/snmp/nvidia
    ```

2. Make the script executable

    ```bash
    chmod +x /etc/snmp/nvidia
    ```

3. Edit your snmpd.conf file and add:

    ```bash
    extend nvidia /etc/snmp/nvidia
    ```

4. Restart snmpd on your host.

    ```bash
    sudo systemctl restart snmpd
    ```

5. Make sure that nvidia-smi is installed. It usually is, if the driver from Nvida is installed.

    The GPU numbers on the graphs agree with how the nvidia-smi
    sees them as being.

    For questions about what the different values are/mean, refer to the
    nvidia-smi man file under the section covering dmon.

