# Device Dependencies

You can set one or more parents for a device. The goal:
if all parent devices are down, the alert transports do not
receive unnecessary alerts for the dependent devices. This is very useful
when you have an outage, for example in a branch office. Usually, you
receive hundreds of alerts. But when this is configured
correctly, you receive an alert only for the parent host(s).

There are three methods to configure this feature. The first one is in the
general settings of a device. The other two are in the 'Device
Dependencies' item in the 'Devices' menu. On this page, you can see all
devices with their parents. Click the 'bin' icon to clear
the dependency setting. Click the 'pen' icon to edit
or change the current setting for the selected device. There is also a
'Manage Device Dependencies' button at the top. With it, you can set
parents for multiple devices at the same time.

For an introduction to Device Dependencies, see
our [Youtube video](https://www.youtube.com/watch?v=KMAarVS9QQ8)
