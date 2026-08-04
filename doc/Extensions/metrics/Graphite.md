# Enabling support for Graphite

This module sends all metrics to a remote graphite service. You need
a tool such as Grafana for graphs.

## What you don't get

- Graphs. This is why you need Grafana at this time. You must
  build your own graphs in Grafana.

RRD continues to operate as usual. Thus, LibreNMS itself
continues to operate as usual.

## Configuration

!!! setting "poller/graphite"
    ```bash
    lnms config:set graphite.enable true
    lnms config:set graphite.host 'your.graphite.server'
    lnms config:set graphite.port 2003
    lnms config:set graphite.prefix 'your.metric.prefix'
    ```

You can add a prefix to your metric path, if necessary. If not, the metric
path for Graphite has the form
`hostname.measurement.fieldname`. Interfaces are kept as
`hostname.ports.ifName.fieldname`.

The system sends the same data as the data kept in rrd to Graphite
and records it. You can then create graphs in Grafana to show the
necessary information.

## Graphite Configuration

LibreNMS updates its metrics each 5 minutes. Thus, we recommend this
addition to your storage-schemas.conf.

```
[network]
pattern = your\.metric\.prefix\..*
retentions = 5m:30d,15m:90d,1h:1y
```
