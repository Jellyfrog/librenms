# Install LibreNMS

## Prepare Linux Server

Install a Linux server that has one of the supported operating systems.
Make sure that you select the OS of your server in the tabs below.
You can select NGINX or Apache as the web server. We recommend NGINX.

Connect to the command line of the server. Then do the steps below.
!!! note

    These instructions are for the **root** user.  
    If you are not the root user, add `sudo` before the shell commands (not
    the commands at `mysql>` prompts). As an alternative, use `sudo -s` or
    `sudo -i` to become a user with root permissions for a short time.

**The minimum supported PHP version is @= php.version_min =@. The recommended version is @= php.version_recommended =@**

## Install Required Packages

=== "Ubuntu 26.04"
    === "NGINX"
        ```
        apt install acl curl fping git mariadb-client mariadb-server mtr-tiny nginx-full nmap php-cli php-curl php-fpm php-gd php-gmp php-json php-mbstring php-mysql php-snmp php-xml php-zip python3-command-runner python3-dotenv python3-pip python3-psutil python3-pymysql python3-redis python3-setuptools python3-systemd rrdtool snmp snmpd traceroute unzip whois
        ```

=== "Ubuntu 24.04"
    === "NGINX"
        ```
        apt install lsb-release ca-certificates curl
        curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
        dpkg -i /tmp/debsuryorg-archive-keyring.deb
        echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
        apt update
        apt install acl curl fping git mariadb-client mariadb-server mtr-tiny nginx-full nmap php@= php.version_recommended =@-cli php@= php.version_recommended =@-curl php@= php.version_recommended =@-fpm php@= php.version_recommended =@-gd php@= php.version_recommended =@-gmp php@= php.version_recommended =@-mbstring php@= php.version_recommended =@-mysql php@= php.version_recommended =@-snmp php@= php.version_recommended =@-xml php@= php.version_recommended =@-zip python3-command-runner python3-dotenv python3-pip python3-psutil python3-pymysql python3-redis python3-setuptools python3-systemd rrdtool snmp snmpd traceroute unzip whois
        ```

=== "Debian 12"
    === "NGINX"
        ```
        apt install lsb-release ca-certificates curl
        curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
        dpkg -i /tmp/debsuryorg-archive-keyring.deb
        echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
        apt update
        apt install acl curl fping git mariadb-client mariadb-server mtr-tiny nginx-full nmap php@= php.version_recommended =@-cli php@= php.version_recommended =@-curl php@= php.version_recommended =@-fpm php@= php.version_recommended =@-gd php@= php.version_recommended =@-gmp php@= php.version_recommended =@-mbstring php@= php.version_recommended =@-mysql php@= php.version_recommended =@-snmp php@= php.version_recommended =@-xml php@= php.version_recommended =@-zip python3-dotenv python3-pip python3-psutil python3-pymysql python3-redis python3-setuptools python3-systemd rrdtool snmp snmpd unzip whois
        ```

=== "Debian 13"
    === "NGINX"
        ```
        apt install acl ca-certificates curl fping git lsb-release mariadb-client mariadb-server mtr-tiny nginx-full nmap php-cli php-curl php-fpm php-gd php-gmp php-mbstring php-mysql php-snmp php-xml php-zip python3-command-runner python3-dotenv python3-pip python3-psutil python3-pymysql python3-redis python3-setuptools python3-systemd rrdtool snmp snmpd unzip wget whois
        ```

## Add librenms user

```
useradd librenms -d /opt/librenms -M -r -s "$(which bash)"
```

## Download LibreNMS

```
cd /opt
git clone https://github.com/librenms/librenms.git
```

## Set permissions

```
chown -R librenms:librenms /opt/librenms
chmod 771 /opt/librenms
setfacl -d -m g::rwx /opt/librenms/rrd /opt/librenms/logs /opt/librenms/bootstrap/cache/ /opt/librenms/storage/
setfacl -R -m g::rwx /opt/librenms/rrd /opt/librenms/logs /opt/librenms/bootstrap/cache/ /opt/librenms/storage/
```

## Install PHP dependencies

Change to the LibreNMS user:
```
su - librenms
```

Run the composer wrapper script. Then go back to the root user:
```
./scripts/composer_wrapper.php install --no-dev
exit
```

!!! note
    If you use a proxy for internet access, the script above can fail.
    To correct this, install the `composer` package manually. For a global installation:
    ```
    wget https://getcomposer.org/composer-stable.phar
    mv composer-stable.phar /usr/bin/composer
    chmod +x /usr/bin/composer
    ```

## Set timezone

Refer to <https://php.net/manual/en/timezones.php> for a list of supported
timezones.  Examples of correct values are: "America/New_York", "Australia/Brisbane", "Etc/UTC".
Make sure that date.timezone in php.ini is set to your timezone.

=== "Ubuntu 26.04"
    ```bash
    vi /etc/php/8.5/fpm/php.ini
    vi /etc/php/8.5/cli/php.ini
    ```

=== "Ubuntu 24.04"
    ```bash
    vi /etc/php/@= php.version_recommended =@/fpm/php.ini
    vi /etc/php/@= php.version_recommended =@/cli/php.ini
    ```

=== "Debian 12"
    ```bash
    vi /etc/php/@= php.version_recommended =@/fpm/php.ini
    vi /etc/php/@= php.version_recommended =@/cli/php.ini
    ```

=== "Debian 13"
    ```bash
    vi /etc/php/8.4/fpm/php.ini
    vi /etc/php/8.4/cli/php.ini
    ```

Also set the system timezone.

```
timedatectl set-timezone Etc/UTC
```


## Configure MariaDB

=== "Ubuntu 26.04"
    ```
    vi /etc/mysql/mariadb.conf.d/50-server.cnf
    ```
    
    Add these lines in the `[mariadbd]` section:

    ```
    innodb_file_per_table=1
    lower_case_table_names=0
    ```

=== "Ubuntu 24.04"
    ```
    vi /etc/mysql/mariadb.conf.d/50-server.cnf
    ```

    Add these lines in the `[mysqld]` section:

    ```
    innodb_file_per_table=1
    lower_case_table_names=0
    ```

=== "Debian 12"
    ```
    vi /etc/mysql/mariadb.conf.d/50-server.cnf
    ```

    Add these lines in the `[mysqld]` section:

    ```
    innodb_file_per_table=1
    lower_case_table_names=0
    ```    

=== "Debian 13"
    ```
    vi /etc/mysql/mariadb.conf.d/50-server.cnf
    ```

    Add these lines in the `[mariadbd]` section:

    ```
    innodb_file_per_table=1
    lower_case_table_names=0
    ```


Then restart MariaDB

```
systemctl enable mariadb
systemctl restart mariadb
```
Start the MariaDB client

```
mysql -u root
```

!!! warning
    Change 'password' below to a secure password.

```sql
CREATE DATABASE librenms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'librenms'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON librenms.* TO 'librenms'@'localhost';
exit
```

## Configure PHP-FPM

=== "Ubuntu 26.04"
    ```bash
    cp /etc/php/8.5/fpm/pool.d/www.conf /etc/php/8.5/fpm/pool.d/librenms.conf
    vi /etc/php/8.5/fpm/pool.d/librenms.conf
    ```

=== "Ubuntu 24.04"
    ```bash
    cp /etc/php/@= php.version_recommended =@/fpm/pool.d/www.conf /etc/php/@= php.version_recommended =@/fpm/pool.d/librenms.conf
    vi /etc/php/@= php.version_recommended =@/fpm/pool.d/librenms.conf
    ```

=== "Debian 12"
    ```bash
    cp /etc/php/@= php.version_recommended =@/fpm/pool.d/www.conf /etc/php/@= php.version_recommended =@/fpm/pool.d/librenms.conf
    vi /etc/php/@= php.version_recommended =@/fpm/pool.d/librenms.conf
    ```

=== "Debian 13"
    ```bash
    cp /etc/php/8.4/fpm/pool.d/www.conf /etc/php/8.4/fpm/pool.d/librenms.conf
    vi /etc/php/8.4/fpm/pool.d/librenms.conf
    ```

Change `[www]` to `[librenms]`:
```
[librenms]
```

Change `user` and `group` to "librenms":
```
user = librenms
group = librenms
```

Change `listen` to a unique path. The path must be the same as in your web server configuration (`fastcgi_pass` for NGINX, `SetHandler` for Apache):
```
listen = /run/php-fpm-librenms.sock
```

If there are no other PHP web applications on this server, you can remove www.conf to save resources.
You can adjust the performance settings in librenms.conf if necessary.

## Configure Web Server

=== "Ubuntu 26.04"
    === "NGINX"
        ```bash
        vi /etc/nginx/conf.d/librenms.conf
        ```

        Add the configuration below. Change `server_name` if necessary:

        ```nginx
        server {
         listen      80;
         server_name librenms.example.com;
         root        /opt/librenms/html;
         index       index.php;

         charset utf-8;
         gzip on;
         gzip_types text/css application/javascript text/javascript application/x-javascript image/svg+xml text/plain text/xsd text/xsl text/xml image/x-icon;
         location / {
          try_files $uri $uri/ /index.php?$query_string;
         }
         location ~ [^/]\.php(/|$) {
          fastcgi_pass unix:/run/php-fpm-librenms.sock;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          include fastcgi.conf;
         }
         location ~ /\.(?!well-known).* {
          deny all;
         }
        }
        ```

        ```bash
        rm /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default
        systemctl restart nginx
        systemctl restart php8.5-fpm
        ```

=== "Ubuntu 24.04"
    === "NGINX"
        ```bash
        vi /etc/nginx/conf.d/librenms.conf
        ```

        Add the configuration below. Change `server_name` if necessary:

        ```nginx
        server {
         listen      80;
         server_name librenms.example.com;
         root        /opt/librenms/html;
         index       index.php;

         charset utf-8;
         gzip on;
         gzip_types text/css application/javascript text/javascript application/x-javascript image/svg+xml text/plain text/xsd text/xsl text/xml image/x-icon;
         location / {
          try_files $uri $uri/ /index.php?$query_string;
         }
         location ~ [^/]\.php(/|$) {
          fastcgi_pass unix:/run/php-fpm-librenms.sock;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          include fastcgi.conf;
         }
         location ~ /\.(?!well-known).* {
          deny all;
         }
        }
        ```

        ```bash
        rm /etc/nginx/sites-enabled/default
        systemctl restart nginx
        systemctl restart php@= php.version_recommended =@-fpm
        ```

=== "Debian 12"
    === "NGINX"
        ```bash
        vi /etc/nginx/sites-enabled/librenms.vhost
        ```

        Add the configuration below. Change `server_name` if necessary:

        ```nginx
        server {
         listen      80;
         server_name librenms.example.com;
         root        /opt/librenms/html;
         index       index.php;

         charset utf-8;
         gzip on;
         gzip_types text/css application/javascript text/javascript application/x-javascript image/svg+xml text/plain text/xsd text/xsl text/xml image/x-icon;
         location / {
          try_files $uri $uri/ /index.php?$query_string;
         }
         location ~ [^/]\.php(/|$) {
          fastcgi_pass unix:/run/php-fpm-librenms.sock;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          include fastcgi.conf;
         }
         location ~ /\.(?!well-known).* {
          deny all;
         }
        }
        ```

        ```bash
        rm /etc/nginx/sites-enabled/default
        systemctl reload nginx
        systemctl restart php@= php.version_recommended =@-fpm
        ```

=== "Debian 13"
    === "NGINX"
        ```bash
        vi /etc/nginx/sites-enabled/librenms.vhost
        ```

        Add the configuration below. Change `server_name` if necessary:

        ```nginx
        server {
         listen      80;
         server_name librenms.example.com;
         root        /opt/librenms/html;
         index       index.php;

         charset utf-8;
         gzip on;
         gzip_types text/css application/javascript text/javascript application/x-javascript image/svg+xml text/plain text/xsd text/xsl text/xml image/x-icon;
         location / {
          try_files $uri $uri/ /index.php?$query_string;
         }
         location ~ [^/]\.php(/|$) {
          fastcgi_pass unix:/run/php-fpm-librenms.sock;
          fastcgi_split_path_info ^(.+\.php)(/.+)$;
          include fastcgi.conf;
         }
         location ~ /\.(?!well-known).* {
          deny all;
         }
        }
        ```

        ```bash
        rm /etc/nginx/sites-enabled/default
        systemctl reload nginx
        systemctl restart php8.4-fpm
        ```

## SELinux

=== "Ubuntu 26.04"
    SELinux not enabled by default

=== "Ubuntu 24.04"
    SELinux is not enabled by default

=== "Debian 12"
    SELinux is not enabled by default

=== "Debian 13"
    SELinux is not enabled by default

## Allow access through firewall
=== "Ubuntu 26.04"
    Firewall not enabled by default

=== "Ubuntu 24.04"
    The firewall is not enabled by default

=== "Debian 12"
    The firewall is not enabled by default

=== "Debian 13"
    The firewall is not enabled by default

## Enable lnms command completion

With this feature, you can use the tab key to complete lnms commands,
the same as for usual Linux commands.

```
ln -s /opt/librenms/lnms /usr/bin/lnms
cp /opt/librenms/misc/lnms-completion.bash /etc/bash_completion.d/
```

`lnms config` is the recommended method for [Configuration](../Support/Configuration.md)


## Configure snmpd (v2c)

If you want to use SNMPv3, [refer to these examples](../Support/SNMP-Configuration-Examples.md/#linux-snmpd-v3)

```
cp /opt/librenms/snmpd.conf.example /etc/snmp/snmpd.conf
```

```
vi /etc/snmp/snmpd.conf
```

Replace the text `RANDOMSTRINGGOESHERE` with your community string.

```
curl -o /usr/bin/distro https://raw.githubusercontent.com/librenms/librenms-agent/master/snmp/distro
chmod +x /usr/bin/distro
systemctl enable snmpd
systemctl restart snmpd
```

## Cron job

```
cp /opt/librenms/dist/librenms.cron /etc/cron.d/librenms
```

!!! note
    By default, cron uses only a small set of environment variables.
    It is possible that you must configure proxy variables for cron.
    As an alternative, you can add the proxy settings in config.php.
    You create the config.php file in the steps that follow. Read this
    page after you complete the LibreNMS installation steps:
    <@= config.site_url =@/Support/Configuration/#proxy-support>

## Enable the scheduler

```
cp /opt/librenms/dist/librenms-scheduler.service /opt/librenms/dist/librenms-scheduler.timer /etc/systemd/system/

systemctl enable librenms-scheduler.timer
systemctl start librenms-scheduler.timer
```

## Enable logrotate

LibreNMS keeps logs in `/opt/librenms/logs`. These logs can become
large. To rotate the old logs, use the supplied logrotate
configuration file:

```
cp /opt/librenms/misc/librenms.logrotate /etc/logrotate.d/librenms
```

## Web installer

Open the web installer. Then do the steps shown on the screen.

<http://librenms.example.com/install>

It is possible that the web installer tells you to manually create a
`config.php` file in your LibreNMS installation location, and to copy
the content on the screen into the file. If you do this, you must set
the permissions on config.php after you copy the content into the
file. Run:

```
chown librenms:librenms /opt/librenms/config.php
```

## Final steps

The installation is complete. You can now log in to
<http://librenms.example.com/>.

!!! danger
    This example does not include the HTTPS setup. Thus your LibreNMS
    installation is not secure by default. Do not make it available on
    the public internet before you configure HTTPS and make the web
    server safe.

## Add the first device

We recommend that you add localhost as your first device in the WebUI.
<https://librenms.example.com/addhost>

## Troubleshooting

If you have problems with your installation, run validate. It does
basic checks and shows the recommended corrections:

```
sudo su - librenms
./validate.php
```

The LibreNMS website shows the available support options:
<https://www.librenms.org/#support>

## What next?

After you install LibreNMS, we recommend that you read these
documents:

- [Performance tuning](../Support/Performance.md)
- [Alerting](../Alerting/index.md)
- [Device Groups](../Extensions/Device-Groups.md)
- [Auto discovery](../Extensions/Auto-Discovery.md)
- [High Availability](../Support/High-Availability.md)

## Closing

We hope that you like LibreNMS. If you do, you can opt in to the
stats system. Refer to [this
page](../General/Callback-Stats-and-Privacy.md) for
what it is and how to enable it.

If you want to help make LibreNMS better, there are [many ways to
help](../Support/FAQ.md#faq9). You
can also [back LibreNMS on Open Collective](https://t.libren.ms/donations).
