hide_toc: true

# Metric storage

By default, we send all metrics to RRD files, directly or through
[RRDCached](RRDCached.md). In addition,  you can send metrics to
Graphite, InfluxDB (v1 or v2 API), OpenTSDB or Prometheus. At this time, you cannot use
these backends to show graphs in LibreNMS. You must use
a tool such as [Grafana](https://grafana.com/).

For more information about how to configure LibreNMS to send data to one of
the other backends, refer to the documentation below.

- [Graphite](metrics/Graphite.md)
- [InfluxDB](metrics/InfluxDB.md)
- [InfluxDBv2](metrics/InfluxDBv2.md)
- [OpenTSDB](metrics/OpenTSDB.md)
- [Prometheus](metrics/Prometheus.md)
