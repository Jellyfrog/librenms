## SNMP Trap

SNMP traps are the standard method to push alert notifications to a northbound NMS or
event correlation system. This transport sends **SNMPv2c TRAPs or INFORMs** that carry
structured varbind data. The alert template fully defines this data. Thus, you have
full control of the OIDs and values that are included.

The transport uses the system `snmptrap` binary (configurable under
**Settings → External → Binaries → snmptrap**).

### Requirements

- Net-SNMP tools installed on the LibreNMS host (`snmptrap` binary).
- A MIB file, accessible on the LibreNMS host, that tells the trap structure.
  The LibreNMS-contributed MIB (`LIBRENMS-NOTIFICATIONS-MIB`) is supplied under
  `mibs/librenms/` and covers the default alert template.

### Configuration

| Setting | Default | Description |
| ------- | ------- | ----------- |
| Destination Host | — | Hostname or IP of the trap receiver |
| Destination Port | `162` | UDP/TCP port on the receiver |
| Transport | `UDP` | `UDP` or `TCP` |
| Community | `public` | SNMPv2c community string |
| Trap OID | `LIBRENMS-NOTIFICATIONS-MIB::defaultAlertEvent` | Notification OID set in the MIB |
| PDU Type | `TRAPv2` | `TRAPv2` (one-way) or `INFORM` (acknowledged) |
| MIB Directory | `/opt/librenms/mibs/librenms` | Directory that contains the MIB file(s) |

**Example:**

| Config | Example |
| ------ | ------- |
| Destination Host | noc.example.com |
| Destination Port | 162 |
| Transport | UDP |
| Community | monitoring |
| Trap OID | LIBRENMS-NOTIFICATIONS-MIB::defaultAlertEvent |
| PDU Type | TRAPv2 |
| MIB Directory | /opt/librenms/mibs/librenms |

### Alert Templates

The system reads the message body that the alert template makes as a sequence of
**varbind lines**. Each line has the form:

```
OID type value
```

where `type` is a Net-SNMP type character (`s` = string, `i` = integer,
`t` = timeticks, `o` = OID, …) and `value` can be a double-quoted string
that contains spaces.  Lines that start with `#` are comments.

#### Catch-All Template (LIBRENMS-NOTIFICATIONS-MIB)

Create an alert template with the name **SNMP Trap — Default** and the
body below.  Attach it to transports that refer to
`LIBRENMS-NOTIFICATIONS-MIB::defaultAlertEvent`.

```
defaultAlertTitle s "{{ $alert->title }}"
defaultAlertID i {{ $alert->id }}
defaultAlertEventID i {{ $alert->uid }}
defaultAlertState i {{ $alert->state }}
defaultAlertSeverity s "{{ $alert->severity }}"
defaultAlertRuleID i {{ $alert->rule_id }}
defaultAlertRuleName s "{{ $alert->name }}"
defaultAlertProcedure s "{{ $alert->proc }}"
defaultAlertTimestamp s "{{ $alert->timestamp }}"
@if ($alert->state == 0)
defaultAlertTimeElapsed s "{{ $alert->elapsed }}"
@endif
defaultAlertDeviceID i {{ $alert->device_id }}
defaultAlertDevHostname s "{{ $alert->hostname }}"
defaultAlertDevSysName s "{{ $alert->sysName }}"
defaultAlertDevMgmtIP s "{{ $alert->ip }}"
defaultAlertDevOS s "{{ $alert->os }}"
defaultAlertDevType s "{{ $alert->type }}"
defaultAlertDevHardware s "{{ $alert->hardware }}"
defaultAlertDevVersion s "{{ $alert->version }}"
defaultAlertDevLocation s "{{ $alert->location }}"
defaultAlertDevUptime t {{ $alert->uptime }}
defaultAlertDevShortUptime s "{{ $alert->uptime_short }}"
defaultAlertACKNotes s "{{ $alert->alert_notes }}"
@if ($alert->faults)
@foreach ($alert->faults as $key => $value)
defaultAlertFaultDetail.{{ $key }} s "{{ $value['string'] }}"
@endforeach
@endif
```

### MIB Installation

Copy the applicable MIB directory to the LibreNMS host and configure the path:

```bash
# LibreNMS MIB (default)
cp -r /opt/librenms/mibs/librenms /opt/librenms/mibs/librenms
```

To make the MIB available globally to Net-SNMP tools:

```bash
cp /opt/librenms/mibs/librenms/LIBRENMS-NOTIFICATIONS-MIB \
   /usr/share/snmp/mibs/
```
