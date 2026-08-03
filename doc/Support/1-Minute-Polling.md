# 1-Minute Polling

LibreNMS can poll data at the intervals that you need.

> Important information:

- If you only want faster up/down alerts, [Fast Ping](../Extensions/Fast-Ping-Check.md) is a much
  easier method.
- You must also change your cron entry for `poller-wrapper.py`
  (if you change from the default 300 seconds).
- Your polling _MUST_ complete in the time that you configure for the
  heartbeat step value. Refer to `/poller` in your WebUI for
  your current value.
- This has an effect only on RRD files that are created after you
  change your settings.
- This change has an effect on all data storage systems, such as MySQL,
  RRD and InfluxDB. If you decrease the values, the space that MySQL
  and InfluxDB use increases.
- We **strongly recommend** that you configure some [performance
  optimizations](Performance.md). All your devices write all graphs
  to the disk each minute, and each device
  has many graphs. The most important item is possibly the
  [RRDCached](../Extensions/RRDCached.md) configuration, which can
  prevent many write IOPS.

To make the changes, go to `/settings/poller/rrdtool/`
in your WebUI. Select RRDTool Setup. Then update the two values
for the step and heartbeat intervals:

- Step is the interval at which you insert data. For 1
  minute polling, set this to 60.
- Heartbeat is the time to wait for data before the system records a
  null value, for example 120 seconds.

## Converting existing RRD files

We supply a basic script that converts the default rrd files to
your configured step and heartbeat values. Make a backup of
your RRD files before you run this script. The
script can run for one device or for all devices at the same time.

> The rrd files must be accessible from the server on which you run this script.

`lnms maintenance:rrd-step`

This shows the help information. To run it for localhost, run:

`lnms maintenance:rrd-step localhost`
