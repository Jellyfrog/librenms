
## MySQL

### Install prereqs
The MySQL script requires PHP-CLI and the PHP MySQL extension, so
make sure that those are installed.

!!! note "" 
    This can be different, related to the PHP version
        
=== "Debian/Ubuntu"

    ```bash
    apt-get install php-cli php-mysql
    ```

=== "CentOS/RedHat"

    ```bash
    yum install php-cli php-mysql
    ```

### Create the cache directory

Create the cache directory, '/var/cache/librenms/' and make sure
that it is owned by the user running the SNMP daemon.

```bash
mkdir -p /var/cache/librenms/
```

### MySQL User
Refer to the [Percona Documentation](https://docs.percona.com/percona-monitoring-and-management/2/setting-up/client/mysql.html#create-a-database-account-for-pmm) for details on how to create a MySQL user with privileges to read the required monitoring data from MySQL / MariaDB.

### MySQL script
Unlike most other scripts, the MySQL script requires a configuration
file `mysql.cnf` in the same directory as the extend or agent script
with following content:

```php
<?php
$mysql_user = 'root';
$mysql_pass = 'toor';
$mysql_host = 'localhost';
$mysql_port = 3306;
```

Note: related to your MySQL installation (a chrooted installation for example),
it is possible that you must specify 127.0.0.1, not localhost. Localhost makes
a MySQL connection through the mysql socket. 127.0.0.1 makes a standard
IP connection to mysql.

Note also if you get a mysql error `Uncaught TypeError: mysqli_num_rows(): Argument #1`,
this is because you are using a newer mysql version which doesnt support `UNBLOCKING` for slave statuses,
so you need to also include the line `$chk_options['slave'] = false;` into `mysql.cnf` to skip checking slave statuses

=== "Agent"

    [Install the agent](../Agent-Setup.md) on this device, if it is not already

    and copy the `mysql` script to `/usr/lib/check_mk_agent/local/`

    Verify it is working by running `/usr/lib/check_mk_agent/local/mysql`

=== "SNMP extend"

    1. Copy the mysql script to the desired host.

        ```bash
        wget https://github.com/librenms/librenms-agent/raw/master/snmp/mysql -O /etc/snmp/mysql
        ```

    2. Make the file executable

        ```bash
        chmod +x /etc/snmp/mysql
        ```

    3. Edit `/etc/snmp/mysql` to set your MySQL connection constants or declare them in `/etc/snmp/mysql.cnf` (new file)

    4. Edit your snmpd.conf file and add:

        ```bash
        extend mysql /etc/snmp/mysql
        ```

    5. Restart snmpd.

    The system discovers the application automatically, as given at the top of
    the page. If it does not, do the steps given under the `SNMP
    Extend` heading at the top of the page.
