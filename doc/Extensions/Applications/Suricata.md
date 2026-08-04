
# Suricata

## SNMP Extend

1. Install the extend.
=== "Debian/Ubuntu"

    ```bash
    apt-get install libjson-perl libfile-path-perl libfile-slurp-perl libmime-base64-perl cpanminus
    cpanm Suricata::Monitoring
    ```

=== "FreeBSD"

    ```bash
    pkg install p5-JSON p5-File-Path p5-File-Slurp p5-Time-Piece p5-MIME-Base64 p5-Hash-Flatten p5-Carp p5-App-cpanminus
    cpanm Suricata::Monitoring
    ```

=== "Generic"

    ```bash
    cpanm Suricata::Monitoring
    ```


2. Setup cron. Below is a example.

    ```
    */5 * * * * /usr/local/bin/suricata_stat_check > /dev/null
    ```

3. Configure snmpd.conf

    ```bash
    extend suricata-stats /usr/bin/env PATH=/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/sbin:/usr/local/bin suricata_stat_check -c
    ```

Or, if you want, you can try to compress the return with Base64+GZIP...

    ```bash
    extend suricata-stats /usr/bin/env PATH=/sbin:/bin:/usr/sbin:/usr/bin:/usr/local/sbin:/usr/local/bin suricata_stat_check -c -b
    ```

4. Restart snmpd on your system.

Make sure that Suricata is set to write the stats
to the eve file one time each minute. This helps make sure that
it is not too far back in the file, and makes sure that it is
recent when the cronjob runs.

Do all configuration of suricata_stat_check in the cron
setup. If the default does not work, check the docs for it at
[MetaCPAN for
suricata_stat_check](https://metacpan.org/dist/Suricata-Monitoring/view/bin/suricata_stat_check)
