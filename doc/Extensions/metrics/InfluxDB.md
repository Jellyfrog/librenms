# Enabling support for InfluxDB

Before we start, it is important that you know
that InfluxDB support is alpha at best at this time. It only
sends data to an InfluxDB installation. Because InfluxDB itself
changes continuously, we cannot
make sure that your data stays correct. If you enable this support, the
risk is yours!

## Requirements

- InfluxDB >= 0.94 < 2.0
- Grafana

The setup of the above is not part of this document, and we cannot
give help with it.

## What you don't get

- Graphs. This is why you need Grafana at this time. You must
  build your own graphs in Grafana.
- Support for InfluxDB or Grafana. We strongly recommend that you
  have some experience with these.

RRD continues to operate as usual. Thus, LibreNMS itself
continues to operate as usual.

## Configuration

!!! setting "poller/influxdb"
    ```bash
    lnms config:set influxdb.enable true
    lnms config:set influxdb.transport http
    lnms config:set influxdb.host '127.0.0.1'
    lnms config:set influxdb.port 8086
    lnms config:set influxdb.db 'librenms'
    lnms config:set influxdb.username 'admin'
    lnms config:set influxdb.password 'admin'
    lnms config:set influxdb.timeout 0
    lnms config:set influxdb.batch_size 0
    lnms config:set influxdb.measurements ''
    lnms config:set influxdb.verifySSL false
    lnms config:set influxdb.debug false
    ```

Credentials are not necessary if you do not use InfluxDB authentication.

The system sends the same data as the data kept in rrd to InfluxDB
and records it. You can then create graphs in Grafana to show the
necessary information.
