## WLED

With this, alerts can set WLED presets.

This needs this information: the hostname/IP of the WLED instance, and the IDs
of the presets that you use. The ID for the preset is the number adjacent to its name in
the WLED web interface.

If you keep warning, critical, or recovery empty, the system ignores that severity/state.

If you use warning, critical, and recovery together, the
results can be unwanted. Also, we recommend this transport only for specified
alerts/hosts, because it can send only limited information.

**Examples:**

Set the preset on WLED at 10.1.2.3 to 1 for warnings and 3 for recoveries.

| Config   | Example  |
|----------|----------|
| Host     | 10.1.2.3 |
| Warning  | 1        |
| Critical |          |
| Recovery | 3        |

Set the preset on WLED at 10.1.2.3 to 2 for criticals and 3 for recoveries.

| Config   | Example  |
|----------|----------|
| Host     | 10.1.2.3 |
| Warning  |          |
| Critical | 2        |
| Recovery | 3        |

Set the preset on WLED at 10.1.2.3 to 1 for warnings and 2 for criticals.

| Config   | Example  |
|----------|----------|
| Host     | 10.1.2.3 |
| Warning  | 1        |
| Critical | 2        |
| Recovery |          |

Set the preset on WLED at 10.1.2.3 to 2 for criticals.

| Config   | Example  |
|----------|----------|
| Host     | 10.1.2.3 |
| Warning  |          |
| Critical | 2        |
| Recovery |          |
