# Enabling support for InfluxDBv2

Before we start, it is important that you know
that InfluxDBv2 support is alpha at best at this time. It only
sends data to an InfluxDBv2 bucket. Because InfluxDB itself
changes continuously, we cannot
make sure that your data stays correct. If you enable this support, the
risk is yours!

It is also important to know that InfluxDBv2 supports only the
InfluxDBv2 API used in InfluxDB version 2.0 or higher. If you
want to send data to a different version of InfluxDB, use
the InfluxDB datastore.

## Requirements

- InfluxDB >= 2.0

The setup of the above is not part of this document, and we cannot
give help with it.

## What you don't get

- Support for InfluxDB. We strongly recommend that you
  have some experience with it.

RRD continues to operate as usual. Thus, LibreNMS itself
continues to operate as usual.

## Configuration

!!! setting "poller/influxdbv2"
    ```bash
    lnms config:set influxdbv2.enable true
    lnms config:set influxdbv2.transport http
    lnms config:set influxdbv2.host '127.0.0.1'
    lnms config:set influxdbv2.port 8086
    lnms config:set influxdbv2.bucket 'librenms'
    lnms config:set influxdbv2.token 'admin'
    lnms config:set influxdbv2.allow_redirect true
    lnms config:set influxdbv2.organization 'librenms'
    lnms config:set influxdbv2.debug false
    lnms config:set influxdbv2.log_file '/opt/librenms/logs/influxdbv2.log'
    lnms config:set influxdbv2.groups-exclude ["group_name_1","group_name_2"]
    lnms config:set influxdbv2.timeout 5
    lnms config:set influxdbv2.verify false
    lnms config:set influxdbv2.batch_size 1000
    lnms config:set influxdbv2.max_retry 2
    ```

The system sends the same data as the data kept in rrd to InfluxDB
and records it. You can then create graphs in Grafana or InfluxDB to show the
necessary information.

Note: polling becomes slower when the poller cannot get access to InfluxDBv2, or cannot write data to it.
