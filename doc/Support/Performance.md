# Performance optimisations

This document gives you guidance on how to make your setup faster.

The suggestions are approximately in the order of their effect.

## RRDCached

**We fully recommend that you run this. It decreases the IO load**. [RRDCached](../Extensions/RRDCached.md)

## MySQL Optimisation

After MySQL runs for 24 hours, we recommend that you run [MySQL
Tuner](https://raw.githubusercontent.com/major/MySQLTuner-perl/master/mysqltuner.pl).
It makes suggestions for changes that are applicable to your setup.

One recommendation from us: set the value below in my.cnf,
under a [mysqld] group:

```bash
innodb_flush_log_at_trx_commit = 0
```

You can also set this to 2. Then, if MySQL or your server stops
suddenly, you can lose up to 1 second of mysql data. But the decrease
in IO use is very large.

## Polling modules

Examine the graph of poller module time under gear > pollers >
performance. It shows the modules that use poller time. This data
is shown for each device under device > graphs > poller.

Disable the polling (and discovery) modules that you do not need. You can
do this globally with `lnms config:set poller_module.<module>` like:

Disable OSPF polling

!!! setting "poller/poller_modules"
    ```bash
    lnms config:set poller_modules.ospf false
    ```

You can disable modules globally, and then enable a module again for one
device. The opposite is also possible. For a list of modules, refer to [Poller
modules](../Support/Poller%20Support.md)

## SNMP Max Repeaters

There is support for SNMP Max repeaters. This is useful on devices
where we poll many ports or bgp sessions, for example, and
where snmpwalk or snmpbulkwalk is used. You must enable this for each
device, under edit device -> snmp -> Max repeaters.

You can also set this globally with the config option:

!!! setting "poller/snmp"
    ```bash
    lnms config:set snmp.max_repeaters X
    ```

To find the best value, we recommend that you measure the time for an
snmpwalk of IF-MIB or a similar MIB. To do this, run the command
below. Replace -REPEATERS- with different numbers from 10 up to
approximately 50. You must also set the correct snmp version,
hostname and community string:

```bash
time snmpbulkwalk -v2c -cpublic HOSTNAME -Cr-REPEATERS- -M /opt/librenms/mibs -m IF-MIB IfEntry
```

!!! warning
    Do not set this value without tests. An incorrect value can make
    polling worse.

## SNMP Max OIDs

For sensor polling, we do bulk snmpgets to make polling faster. The
default is ten. You can change this for each device, under edit
device -> snmp -> Max OIDs.

You can also set this globally with the config option:

!!! setting "poller/snmp"
    ```bash
    lnms config:set snmp.max_oid X
    ```

!!! warning
    We recommend that you monitor sensor polling when you change this.
    Make sure that you do not set the value too high.

## fping tuning

You can change some of the default fping options globally or for each
device. The default values are:

!!! setting "poller/ping"
    ```bash
    lnms config:set fping_options.timeout 500
    lnms config:set fping_options.count 3
    lnms config:set fping_options.interval 500
    ```

If your devices are slow to reply, you must increase the
timeout value, and possibly the interval value. But if your
network is stable, you can make the poller faster. Decrease the
count value to 1 and/or the timeout+millsec value to 200 or 300:

!!! setting "poller/ping"
    ```bash
    lnms config:set fping_options.timeout 300
    lnms config:set fping_options.count 1
    lnms config:set fping_options.interval 300
    ```

Then we no longer delay each icmp packet that we send (we send
3 in total by default) by 0.5 seconds. When we send only 1 icmp
packet, we receive a reply more quickly. With the default values,
a reply takes a minimum of 1 second, independently of how
quickly the icmp packet comes back.

## Optimise poller-wrapper

The default for `poller-wrapper.py` is 16 threads. This is not always
the best value. A general rule is 2 threads for each core. But we
recommend that you decrease or increase the number until you
find the best value.

!!! note
    KEEP in MIND that this does not always help. The effect is related
    to your system and CPU. Thus, be careful. To change this, go to the
    cron job for librenms, usually in `/etc/cron.d/librenms`, and change
    the "16"

```
*/5  *    * * *   librenms    /opt/librenms/cronic /opt/librenms/poller-wrapper.py 16
```

If you use the Dispatch Service, you can adjust the number of threads
in the WebUI. Refer to [Dispatcher Service](../Extensions/Dispatcher-Service.md)

## Recursive DNS

If your installation uses hostnames for devices, and you have many
devices, we recommend a local recursive dns instance on the
LibreNMS server. You can use a program such as pdns-recursor. Then
configure `/etc/resolv.conf` to use 127.0.0.1 for queries.

## Per port polling

By default, the polling ports module walks ifXEntry + some items
from ifEntry, independently of the port status. Thus, if a port is
marked as deleted (because you do not want to see it), or if it is
disabled, we collect data for it. Usually this is satisfactory,
because the walks are quick. But this method is not the best for
devices that have many ports, of which a good percentage are deleted
or disabled. For these devices, you can enable 'selected port polling'
for each device in the edit device -> misc section, or enable it
globally (**not recommended**):

!!! setting "poller/ports"
    ```bash
    lnms config:set polling.selected_ports true
    ```

We do not recommend the global setting, because tests show that it
increases the cpu usage of your poller. You can also set it for a specified OS:

!!! setting "poller/ports"
    ```bash
    lnms config:set os.ios.polling.selected_ports true
    ```

Run `./scripts/collect-port-polling.php` as the `librenms` user.
It polls your devices with full polling and with selective polling,
and shows a table with the difference. Optionally, it can enable or
disable selected ports polling for the devices where a change helps.
Note: it does not examine this again continuously. The values change
only when you run the script. There are some options:

```bash
-h <device id> | <device hostname wildcard>  Poll single device or wildcard hostname
-e <percentage>                              Enable/disable selected ports polling for devices which would benefit <percentage> from a change
```

If you want the script to set selected port polling on the devices
where the measured change is **10% or more**, run it with
`./scripts/collect-port-polling.php -e 10`. But note: it does not
use only the 10%. There is a second condition: the change
must be more than one second of polling time.

## Web interface

### HTTP/2

If you run https, enable http/2 support in
the web server that you use:

For Nginx (1.9.5 and above), change `listen 443 ssl;` to `listen 443
ssl http2;` in the Virtualhost config.

For Apache (2.4.17 and above), set `Protocols h2 http/1.1` in the Virtualhost config.

## PHP-opcache

A correct `php-opcache` setup gives much better performance.

**Note: Memory based caching with PHP cli increases memory usage and makes operation slower. File based caching is not as fast as memory based caching, and stale cache problems are more probable.**

Some distributions permit separate cli, mod_php and php-fpm configurations. We can use this to set the best config.

### For web servers using mod_php and php-fpm

Update your web PHP opcache.ini.  Possible locations: `/etc/php/8.3/fpm/conf.d/opcache.ini`, `/etc/php.d/opcache.ini`, or `/etc/php/conf.d/opcache.ini`.

```ini
zend_extension=opcache
opcache.enable=1
opcache.memory_consumption=256
```

If you have cache problems, restart httpd or php-fpm to clear the opcache.

### For pollers

First, create a cache directory that the librenms user can write to:
`sudo mkdir -p /tmp/cache && sudo chmod 775 /tmp/cache && sudo chown -R librenms /tmp/cache`

Update your PHP opcache.ini.  Possible locations: `/etc/php/8.3/cli/conf.d/opcache.ini`, `/etc/php.d/opcache.ini`, or `/etc/php/conf.d/opcache.ini`.

```ini
zend_extension=opcache.so
opcache.enable=1
opcache.enable_cli=1
opcache.file_cache="/tmp/cache/"
opcache.file_cache_only=0
opcache.file_cache_consistency_checks=1
opcache.memory_consumption=256
```

If you have cache problems, you can clear the file based opcache with `rm -rf /tmp/cache`.

Debian 12 users: the current stable php 8.2 version (8.2.7) causes segmentation faults when opcache uses the file cache. The problem is possibly this one: https://github.com/php/php-src/issues/10914 
To remove the problem, use the sury packages or disable the file cache
