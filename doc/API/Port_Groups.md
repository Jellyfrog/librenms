### `get_port_groups`

List all port groups.

Route: `/api/v0/port_groups`

Examples:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/port_groups
```

Output:

```json
[
    {
        "status": "ok",
        "message": "Found 1 port groups",
        "count": 1,
        "groups": [
        {
            "id": "1",
            "name": "Testing",
            "desc": "Testing"
        }
        ]
    }
]
```

### `get_ports_by_group`

List all ports that match the given group.

Route: `/api/v0/port_groups/:name`

- name Is the name of the port group which you can get with
  [`get_port_groups`](#get_port_groups). Make sure that
  the name is urlencoded if necessary (i.e Linux Servers must
  be urlencoded.

Params:

- full: set to any value to return all data for the devices in a given group

Examples:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/port_groups/Billable
```

Output:

```json
{
    "status": "ok",
    "ports": [
        {
            "port_id": 1376
        },
        {
            "port_id": 2376
        }
    ],
    "count": 2
}
```

### `add_port_group`

Add a new port group. On success, you get the ID of the new port group,
and the HTTP response code is `201`.

Route: `/api/v0/port_groups`

Input (JSON):

- `name`: *required* - The name of the port group
- `desc`: *optional* - Description of the port group

Examples:

Dynamic Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' \
  -X POST \
  -d '{"name": "New Port Group", \
       "desc": "A very fancy port group"}' \
  https://foo.example/api/v0/port_groups
```

Output:

```json
{
    "status": "ok",
    "id": 86,
    "message": "Port group New Port Group created"
}
```

### `assign_port_group`

Attach a Port Group to a list of Ports

Route: `/api/v0/port_groups/:port_group_id/assign`

Input (JSON):

- `port_ids`: *required* - List of Port Ids

Examples:

Dynamic Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' -X POST -d '{"port_ids": ["4","34","25,"983"]}' https://foo.example/api/v0/port_groups/3/assign
```

Output:

```json
{
    "status": "ok",
    "Port Ids 4, 34, 25, 983 have been added to Port Group Id 3": 200
}
```

### `remove_port_group`

Remove a Port Group from a list of Ports

Route: `/api/v0/port_groups/:port_group_id/remove`

Input (JSON):

- `port_ids`: *required* - List of Port Ids

Examples:

Dynamic Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' -X POST -d '{"port_ids": ["4","34","25,"983"]}' https://foo.example/api/v0/port_groups/3/remove
```

Output:

```json
{
    "status": "ok",
    "Port Ids 4, 34, 25, 983 have been removed from Port Group Id 3": 200
}
```

