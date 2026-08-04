# Sneck

This is for replacing Nagios/Icinga or the LibreNMS service
integration for NRPE. With this, LibreNMS can query what
checks were ran on the server and keep track of totals of OK, WARNING,
CRITICAL, and UNKNOWN statuses.

The big advantage over this compared to a NRPE are as below.

- It does not need to know what checks are configured on it.
- Also, it does not have to wait for the tests to run. Sneck
  runs with cron, and then returns the cache when SNMP queries it.
  This means a much faster response time, specially when slow checks
  run.
- Works over proxied SNMP connections.

Included are alert examples. Although for setting up custom ones, the
metrics below are provided.

| Metric              | Description                                                                                                           |
|---------------------|-----------------------------------------------------------------------------------------------------------------------|
| ok                  | Total OK checks                                                                                                       |
| warning             | Total WARNING checks                                                                                                  |
| critical            | Total CRITICAL checks                                                                                                 |
| unknown             | Total UNKNOWN checks                                                                                                  |
| errored             | Total checks that errored                                                                                             |
| time_to_polling     | Difference in seconds between when polling data was generated and when polled                                         |
| time_to_polling_abs | The absolute value of time_to_polling.                                                                                |
| check_$CHECK        | Exit status of one check. `$CHECK` is equal to the name of the applicable check. Thus, `foo` is `check_foo` |

The standard Nagios/Icinga style exit codes are used and those are as
below.

| Exit | Meaning  |
|------|----------|
| 0    | okay     |
| 1    | warning  |
| 2    | critical |
| 3+   | unknown  |

To use `time_to_polling`, you must enable it. Set the
config item below. The default is false. Unless set to true, this
value default is 0. If you enable this, make sure
that NTP is in use in all locations. If not, an alert occurs when it goes over a
difference of 540s.

```
lnms config:set app.sneck.polling_time_diff true
```

For more information on Sneck, check it out at
[MetaCPAN](https://metacpan.org/dist/Monitoring-Sneck) or
[Github](https://github.com/VVelox/Monitoring-Sneck).

To poll systems that use Sneck through the CLI, you can also use
boop_snoot. Its docs are
at [MetaCPAN](https://metacpan.org/dist/Monitoring-Sneck-Boop_Snoot) and
[Github](https://github.com/VVelox/Monitoring-Sneck-Boop_Snoot).

## Install prerequisites

=== "Debian/Ubuntu"

    ```bash
    apt-get install cpanminus libjson-perl libfile-slurp-perl libmime-base64-perl
    cpanm Monitoring::Sneck
    ```

=== "FreeBSD"

    ```bash
    pkg install p5-JSON p5-File-Slurp p5-MIME-Base64 p5-App-cpanminus
    cpanm Monitoring::Sneck
    ```

=== "Generic"

    ```bash
    cpanm Monitoring::Sneck
    ```

## SNMP Extend

2. Configure any of the checks you want to run in
   `/usr/local/etc/sneck.conf`. You con find it documented
   [here](https://metacpan.org/pod/Monitoring::Sneck#CONFIG-FORMAT).

3. Set it up in cron. Then it is not necessary to wait for all
   the checks to complete when SNMP polls it. For SMART
   or other long checks, that wait causes a timeout. It also means that it
   is not necessary to call it with sudo.

    ```bash
    */5 * * * * /usr/bin/env PATH=/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/sbin:/usr/local/bin /usr/local/bin/sneck -u 2> /dev/null > /dev/null
    ```

4. Set it up in the snmpd config and restart snmpd. If calling `sneck -c` instead or catting the non-snmp cache file, there is the possibility
   of snmpd mangling the return. To avoid this cat the snmp cache file as below or call `sneck -c -b`.

    ```bash
    extend sneck /bin/cat /var/cache/sneck.cache.snmp
    ```

5. In LibreNMS, enable the application for the server in question or wait for auto discovery to find it.
