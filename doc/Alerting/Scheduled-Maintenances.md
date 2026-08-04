# Scheduled Maintenances

With Scheduled Maintenances, you can put a device, a location, or a full device group into maintenance mode.
The screwdriver symbol to the left of the device name shows that a device is in maintenance (on its detail page, on
device group pages, and so on). A maintenance changes how the system does alerting and/or alert transporting (aka user
notifications).

## Maintenance Behaviors

A maintenance can have three different behaviors:

- Skip alerts: The current alerts stay as they are, and no alert rule checks occur. Thus, the system does not create
new alerts, and the current alerts do not recover. This is the default behavior.
- Mute alert: The system does alerts as usual (new alerts start, current alerts can recover and so on). But the system stops
each alert transport, such as mail. This is useful if you want "silence" for a period of time, for a
reason of your choice, but you want to continue to see what occurs on your devices.
- Run alerts: This is only a cosmetic maintenance. You see that a device is in maintenance. But this
setting does not stop alerts.

## Managing Maintenances

You get access to the page for Scheduled Maintenance through the main menu (Alert → Scheduled Maintenance). The table shows
all maintenances: future ones, active ones, and completed ones. You can add a new maintenance, and you can also edit and delete
maintenances here (column "Actions").

The form to add and edit maintenances always has fields for Title, Notes, Behavior and "Map To". With the last
field, you can set the devices, device groups, and locations that the maintenance applies to. Locations are entities in a separate
table, and devices refer to them. You can select these here.

The form also has a slider with the label "Recurring". Use this to select one of two types
of maintenances:

- Non-recurring maintenances start at a set time and end at a later time. After that, they are complete and have no
more effect, unless you change the date values again.
- Recurring maintenances have a start date and an end date. Maintenance periods can occur between these dates. You
also set the days, and the start hour and the end hour, for the maintenance. This hour range applies to
each selected day.

For example: You can put a group of devices into maintenance from Monday until Friday, from 10 pm to 11pm, from
01.01.20xx until 31.01.20xx. Dates cannot be in the past. The End Hour/Date must be later than, or the same as, the
Start Hour/Date.

To end a maintenance early, delete it.

## Add Single Device Maintenance

To put one device into maintenance, go to its edit section, and there to the "Device Settings". If the
device is not already in maintenance, you find a green button with the label "Maintenance Mode". Push it
to open a dialogue with settings such as notes, duration, and the behavior given above (the "Skip alerts" option is
the default).

Initially, you can only select a duration of a maximum of 23:30h. But you can change it later. To do this, edit the
applicable maintenance object. The title of the maintenance is always the display name of the device (if set), or its hostname or IP
address. You can also change it later.

If a minimum of one maintenance already applies to the device, the button is orange, with the label "Device already
in maintenance". You cannot manage or remove a device maintenance here.

## Setting a Default Behavior for scheduled maintenance

!!! setting "alerting/scheduled-maintenance"
    ```bash
    lnms config:set alert.scheduled_maintenance_default_behavior 1
    ```

You can use these values:

- 1 = Skip alerts
- 2 = Mute alerts
- 3 = Run alerts
