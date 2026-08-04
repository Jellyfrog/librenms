# PHP-FPM

A small shell script that reports the status of PHP-FPM (FastCGI Process Manager).

### Install prereqs

=== "Debian/Ubuntu"

    ```bash
    apt-get install libfile-slurp-perl libjson-perl libstring-shellquote-perl libmime-base64-perl
    ```

=== "FreeBSD"

    ```bash
    pkg install p5-File-Slurp p5-JSON p5-String-ShellQuote p5-MIME-Base64
    ```

=== "Fedora"

    ```bash
    dnf install perl-JSON perl-File-Slurp perl-String-ShellQuote
    ```

## Agent or SNMP Extend

=== "SNMP Extend"

    1. Copy the shell script, phpfpmsp, to the desired host

        ```bash
        wget https://github.com/librenms/librenms-agent/raw/master/snmp/php-fpm -O /etc/snmp/php-fpm
        ```

    2. Make the script executable

        ```bash
        chmod +x /etc/snmp/php-fpm
        ```

    3. Edit your snmpd.conf file (usually /etc/snmp/snmpd.conf) and add:
    ```
    extend phpfpmsp /etc/snmp/php-fpm
    ```

    4. Create the config file
    `/usr/local/etc/php-fpm_extend.json`. You can specify different locations
    specified using the the `-f` switch. Akin to like below. For more
    information, see `/etc/snmp/php-fpm --help`.

        ```json
        {
        "pools":{
                "thefrog": "https://thefrog/fpm-status",
                "foobar": "https://foo.bar/fpm-status"
            }
        }
        ```

    6. Restart snmpd on the host

        ```bash
        sudo systemctl restart snmpd
        ```

        The system discovers the application automatically, as given at the top of the page. If it does not, do the steps given under the `SNMP Extend` heading at the top of the page.

=== "Agent"

    [Install the agent](../Agent-Setup.md) on this device, if it is not already
    and copy the `phpfpmsp` script to `/usr/lib/check_mk_agent/local/`
