# Network Map

LibreNMS can show you a dynamic network map made from
data collected from devices.  You get access to these maps through
these menu options:

 - Overview -> Maps -> Network
 - Overview -> Maps -> Device Group Maps
 - The Neighbours -> Map tab when viewing a single device
   (the Neighbours tab shows only if a device has xDP neighbours)

These network maps can be based on:

- xDP Discovery
- MAC addresses (ARP entries matching interface IP and MAC)

By default, the two are included. But you can enable / disable each
one with this config option:

```bash
lnms config:set network_map_items '["mac","xdp"]'
```

Remove mac or xdp, as applicable.
XDP is based on FDP, CDP and LLDP support, as applicable to the device type.

Note: the global map can become a large network
map that is slow to draw and use. On large networks, the network map on the
device neighbour page is more usable. As an alternative, build device
groups and use the device group maps.

## Settings
To configure the map display, change the [Vis JS Options](VisJS-Config.md)
