# Enabling support for OpenTSDB

This module sends all metrics to an OpenTSDB server. You need a tool
such as Grafana for graphs.

## Requirements

- OpenTSDB
- Grafana

## What you don't get

 Graphs. This is why you need Grafana at this time. You must
 build your own graphs in Grafana.

RRD continues to operate as usual. Thus, LibreNMS itself
continues to operate as usual.

You can add this to your config:

## Configuration

!!! setting "poller/opentsdb"
    ```bash
    lnms config:set opentsdb.enable true
    lnms config:set opentsdb.host '127.0.0.1'
    lnms config:set opentsdb.port 4242
    ```

The system sends the same data as the data kept in rrd to OpenTSDB
and records it. You can then create graphs in Grafana to show the
necessary information.
