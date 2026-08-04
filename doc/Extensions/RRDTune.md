# RRDTune

When we create rrd files for ports, we do so with a maximum
value of 12500000000 (100G) at this time. Thus, if a device sends bad
data back, it can look as if a 100M port does 40G+, which
is impossible. To prevent this, you can enable the rrdtool tune option.
It sets the maximum value to the physical speed of the interface (with a minimum
of 10M).

You can enable this in three ways!

- Globally under Global Settings -> Poller -> Datastore: RRDTool
- For the actual device, Edit Device -> Misc
- For each port, Edit Device -> Port Settings

Now, when a port interface speed changes (this can occur because of a
physical change, or because the device gave an incorrect report), the system
sets the maximum value. If you do not want to wait until a port speed changes,
you can run the included script:

`lnms port:tune <hostname> <ifName>` 

Wildcards with * are supported, and ifName is optional, i.e:

`lnms port:tune local* eth*`

This script then does the rrdtool tune on each port that it finds,
with the given ifSpeed for that port.

Run `lnms port:tune -h` to see help page.
