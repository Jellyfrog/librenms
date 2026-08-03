## IBM On Call Manager (OCM)

LibreNMS can connect to IBM On Call Manager with a webhook URL. You create this URL when you add the LibreNMS integration.

The webhook URL (with the name `ocm-url`) is under 'Integrations' in the IBM On Call Manager portal, after you select LibreNMS as the integration.

IBM On Call Manager uses the webhook to send the name of the alert rule, together with other applicable details. It includes the name or IP address of the system that sends the alert, the name of the alert, the severity, the timestamp, the OS, the location, and a unique ID. 

**Example:**

| Config  | Example                                  |
| ------- | ---------------------------------------- |
| ocm-url | https://ibm-ocm-webhook.example.com/api |

**Payload Example**:

```json
{
  "eventSource": {
    "name": "{{ $alert->sysName }}",
    "description": "{{ $alert->sysDescr }}",
    "displayName": "LibreNMS Alerts - DBAoC",
    "type": "server",
    "sourceID": "LibreNMS-DBAoC"
  },
  "resourceAffected": {
    "hostname": "{{ $alert->hostname }}",
    "ipAddress": "{{ $alert->ip }}",
    "os": "{{ $alert->os }}",
    "location": "{{ $alert->location }}",
    "component": "{{ $alert->sysName }}"
  },
  "eventInfo": {
    "summary": "{{ $alert->title }}",
    "msg": "{{ $alert->msg }}",
    "severity": "{{ $alert->severity }}",
    "timestamp": "{{ $alert->timestamp }}",
    "uniqueID": "{{ $alert->uid }}"
  }
}
```