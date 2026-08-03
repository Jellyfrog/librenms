## Nagios Compatible

The nagios transport writes to a FIFO at the set location, in the
same format as nagios. With this, you can use other alerting
systems with LibreNMS, for example [Flapjack](http://flapjack.io).

**Example:**

| Config | Example |
| ------ | ------- |
| Nagios FIFO | /path/to/my.fifo |