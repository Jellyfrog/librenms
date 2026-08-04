### `list_pollers`

List all pollers in the system. Returns data from the `poller_cluster` table, if available.
If not, it uses `pollers` with the related stats.

Route: `/api/v0/pollers`

Input:

-

Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/pollers
```

Output (pollers table):

```json
{
    "status": "ok",
    "pollers": [
        {
            "id": 1,
            "poller_name": "localhost",
            "last_polled": "2025-01-15 10:30:00",
            "devices": 50,
            "time_taken": 120.5
        }
    ],
    "count": 1
}
```

Output (poller_cluster with stats):

```json
{
    "status": "ok",
    "pollers": [
        {
            "id": 1,
            "node_id": "abc123",
            "poller_name": "poller1",
            "poller_version": "24.1.0",
            "poller_groups": "0",
            "last_report": "2025-01-15T10:30:00.000000Z",
            "master": 1,
            "stats": [
                {
                    "id": 1,
                    "parent_poller": 1,
                    "poller_type": "poller",
                    "depth": 0,
                    "devices": 50,
                    "worker_seconds": 120.5,
                    "workers": 16,
                    "frequency": 300
                }
            ]
        }
    ],
    "count": 1
}
```

### `list_poller_log`

List all devices with polling information. Returns device polling details, which include the last polled time, the time taken, and poller group information. It shows only active devices to which the user has access.

Route: `/api/v0/pollers/log`

Input:

- `unpolled` (optional): If set, it shows only devices that were not polled for some time (late by 1.2x the rrd.step value, default 300 seconds)

Example:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/pollers/log
```

Example with unpolled filter:

```curl
curl -H 'X-Auth-Token: YOURAPITOKENHERE' https://foo.example/api/v0/pollers/log?unpolled=1
```

Output:

```json
{
    "status": "ok",
    "log": [
        {
            "hostname": "router1.example.com",
            "display_name": "Router 1",
            "last_polled": "2025-01-15 10:30:00",
            "last_polled_timetaken": 2.45,
            "poller_group": "General",
            "poller_group_id": 0
        },
        {
            "hostname": "switch1.example.com",
            "display_name": "Switch 1",
            "last_polled": "2025-01-15 10:29:45",
            "last_polled_timetaken": 1.23,
            "poller_group": "Data Center",
            "poller_group_id": 1
        }
    ],
    "count": 2
}
```
