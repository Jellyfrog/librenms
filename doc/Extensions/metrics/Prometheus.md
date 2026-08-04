# Enabling support for Prometheus

Know that Prometheus support is alpha at best. It did not get
many tests, and it is in development. It only
sends data to a Prometheus PushGateway. Be careful when
you enable this support. The risk is yours!

## Requirements (Older versions can possibly operate, but they are not tested

- Prometheus >= 2.0
- PushGateway >= 0.4.0
- Grafana
- PHP-CURL

The setup of the above is not part of this document, and we cannot
give help with it.

## What you don't get

- Graphs. This is why you need Grafana at this time. You must
  build your own graphs in Grafana.
- Support for Prometheus or Grafana. We strongly recommend that
  you have some experience with these.

RRD continues to operate as usual. Thus, LibreNMS itself
continues to operate as usual.

## Configuration

!!! setting "poller/prometheus"
    ```bash
    lnms config:set prometheus.enable true
    lnms config:set prometheus.url 'http://127.0.0.1:9091'
    lnms config:set prometheus.job 'librenms'
    lnms config:set prometheus.prefix 'librenms'
    ```

If your pushgateway uses basic authentication, configure this:

!!! setting "poller/prometheus"
    ```bash
    lnms config:set prometheus.user username
    lnms config:set prometheus.password password
    ```

Additional settings

!!! setting "poller/prometheus"
    ```bash
    lnms config:set prometheus.attach_sysname true
    ```


## Prefix

When you set the 'prefix' option, all metric names start with 
the configured value.

For example, without this option, metric names are 
like this:

```
OUTUCASTPKTS
ifOutUcastPkts_rate
INOCTETS
ifInErrors_rate
```

With a prefix name, for example 'librenms', those 
metrics show with these names:

```
librenms_OUTUCASTPKTS
librenms_ifOutUcastPkts_rate
librenms_INOCTETS
librenms_ifInErrors_rate
```

## Sample Prometheus Scrape Config (for scraping the Push Gateway)

```yml
- job_name: pushgateway
  scrape_interval: 300s
  honor_labels: true
  static_configs:
    - targets: ['127.0.0.1:9091']
```

The system sends the same data as the data kept in rrd to Prometheus
and records it. You can then create graphs in Grafana to show the
necessary information.
