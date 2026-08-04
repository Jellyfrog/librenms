### `get_devicegroups`

List all device groups.

Route: `/api/v0/devicegroups`

Input (JSON):

  -

Examples:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/devicegroups
```

Output:

```json
[
    {
        "status": "ok",
        "message": "Found 1 device groups",
        "count": 1,
        "groups": [
        {
            "id": "1",
            "name": "Testing",
            "desc": "Testing",
            "pattern": "%devices.status = \"1\" &&"
        }
        ]
    }
]
```

### `add_devicegroup`

Add a new device group. On success, you get the ID of the new device group,
and the HTTP response code is `201`.

Route: `/api/v0/devicegroups`

Input (JSON):

- `name`: *required* - The name of the device group
- `type`: *required* - must be `static` or `dynamic`. When you set this to static,
  the devices input is mandatory
- `desc`: *optional* - Description of the device group
- `rules`: *required if type == dynamic* - A set of rules that finds the
  devices to include in this device group
- `devices`: *present if type == static* - A list of devices to
  include in this group. This is a static list of devices

Examples:

Dynamic Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' \
  -X POST https://foo.example/api/v0/devicegroups \
  --data-raw '
{
 "name": "New Device Group", 
 "desc": "A very fancy dynamic group",
 "type": "dynamic", 
 "rules": "{\"condition\":\"AND\",\"rules\":[{\"id\":\"access_points.name\",\"field\":\"access_points.name\",\"type\":\"string\",\"input\":\"text\",\"operator\":\"equal\",\"value\":\"accesspoint1\"}],\"valid\":true}"
}
'
```

Output:

```json
{
    "status": "ok",
    "id": 86,
    "message": "Device group New Device Group created"
}
```

Static Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' \
  -X POST https://foo.example/api/v0/devicegroups \
  -d '{"name":"New Device Group","type":"static","devices":[261,271]}'
```

Output:

```json
{
    "status": "ok",
    "id": 86,
    "message": "Device group New Device Group created"
}
```

### `update_devicegroup`

Updates a device group.

Route: `/api/v0/devicegroups/:name`

- name Is the name of the device group which you can get with
  [`get_devicegroups`](#get_devicegroups). Make sure that
  the name is urlencoded if necessary (that is, Linux Servers must
  be urlencoded.

Input (JSON):

- `name`: *optional* - The name of the device group
- `type`: *optional* - must be `static` or `dynamic`. When you set this to static,
  the devices input is mandatory
- `desc`: *optional* - Description of the device group
- `rules`: *required if type == dynamic* - A set of rules that finds the
  devices to include in this device group
- `devices`: *required if type == static* - A list of devices to
  include in this group. This is a static list of devices

Examples:

```curl
curl -X PATCH -d '{"name": "NewLinuxServers"}' -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/devicegroups/LinuxServers
```

Output:

```json
{
    "status": "ok",
    "message": "Device group LinuxServers updated"
}
```

### `delete_devicegroup`

Deletes a device group.

Route: `/api/v0/devicegroups/:name`

- name Is the name of the device group which you can get with
  [`get_devicegroups`](#get_devicegroups). Make sure that
  the name is urlencoded if necessary (that is, Linux Servers must
  be urlencoded.

Input:

-

Examples:

```curl
curl -X DELETE -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/devicegroups/LinuxServers
```

Output:

```json
{
    "status": "ok",
    "message": "Device group LinuxServers deleted"
}
```

### `get_devices_by_group`

List all devices that match the given group.

Route: `/api/v0/devicegroups/:name`

- name Is the name of the device group which you can get with
  [`get_devicegroups`](#get_devicegroups). Make sure that
  the name is urlencoded if necessary (that is, Linux Servers must
  be urlencoded.

Input (JSON):

- full: set to any value to return all data for the devices in a given group

Examples:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/devicegroups/LinuxServers
```

Output:

```json
[
     {
         "status": "ok",
         "message": "Found 3 in group LinuxServers",
         "count": 3,
         "devices": [
            {
                "device_id": "15"
            },
            {
                "device_id": "18"
            },
            {
                "device_id": "20"
            }
         ]
     }
]
```

### `maintenance_devicegroup`

Set a device group into maintenance mode.

Route: `/api/v0/devicegroups/:name/maintenance`

Input (JSON):

- `title`: *optional* - A title for the Maintenance.
  If not given, the system uses the device group name
- `behavior`: *optional* - id of the wanted maintenance behavior.
  If not given, the default is alert.scheduled_maintenance_default_behavior
- `notes`: *optional* - A description for the Maintenance
- `start`: *optional* - start time of Maintenance in full format `Y-m-d H:i:00`
  for example, 2022-08-01 22:45:00
  If not given, the system uses the current system time `now()`
- `duration`: *required* - Duration of Maintenance in format `H:i` / `Hrs:Mins`
  for example, 02:00

Example with start time:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' \
  -X POST https://foo.example/api/v0/devicegroups/Cisco%20switches/maintenance/ \
  --data-raw '
{
 "title":"Device group Maintenance",
  "notes":"A 2 hour Maintenance triggered via API with start time",
  "start":"2022-08-01 08:00:00",
  "duration":"2:00"
}
'
```

Output:

```json
{
    "status": "ok",
    "message": "Device group Cisco switches (2) will begin maintenance mode at 2022-08-01 22:45:00 for 2:00h"
}
```

Example with no start time:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' \
  -X POST https://foo.example/api/v0/devicegroups/Cisco%20switches/maintenance/ \
  --data-raw '
{
 "title":"Device group Maintenance",
  "notes":"A 2 hour Maintenance triggered via API with no start time",
  "duration":"2:00"
}
'
```

Output:

```json
{
    "status": "ok",
    "message": "Device group Cisco switches (2) moved into maintenance mode for 2:00h"
}
```

### Add devices to group

Add devices to a device group.

Route: `/api/v0/devicegroups/:name/devices`

- name Is the name of the device group which you can get with
  [`get_devicegroups`](#get_devicegroups). Make sure that
  the name is urlencoded if necessary (that is, Linux Servers must
  be urlencoded.

Input (JSON):

- `devices`: *required* - A list of devices to add to the group.

Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' \
  -X POST https://foo.example/api/v0/devicegroups/LinuxServers/devices \
  --data-raw '{"devices":[261,271]}'
```

Output:

```json
{
    "status": "ok",
    "message": "Devices added"
}
```

### Remove devices from group

Removes devices from a device group.

Route: `/api/v0/devicegroups/:name/devices`

- name Is the name of the device group which you can get with
  [`get_devicegroups`](#get_devicegroups). Make sure that
  the name is urlencoded if necessary (that is, Linux Servers must
  be urlencoded.

Input (JSON):

- `devices`: *required* - A list of devices to remove from the group.

Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' \
  -X DELETE https://foo.example/api/v0/devicegroups/LinuxServers/devices \
  --data-raw '{"devices":[261,271]}'
```

Output:

```json
{
    "status": "ok",
    "message": "Devices removed"
}
```
