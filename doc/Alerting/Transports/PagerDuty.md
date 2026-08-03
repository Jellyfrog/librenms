## PagerDuty

LibreNMS can use PagerDuty. It does this with an API
key and an Integration Key.

API Keys are under 'API Access' in the PagerDuty portal.

Integration Keys are under 'Integration' for the applicable
Service that you created in the PagerDuty portal.

**Example:**

| Config | Example |
| ------ | ------- |
| API Key | randomsample |
| Integration Key | somerandomstring |

**Fixed LibreNMS -> PagerDuty field mappings**

| LibreNMS | PagerDuty |
| -------- | --------- |
| DeviceGroupName | payload.group |
| DeviceType | payload.class |
| Hostname | payload.source |
| Alert severity | payload.severity |
| Alert title | payload.summary |

**Nice formatting**

PagerDuty formats the Custom Details panel well if it receives valid JSON.
At this time, the PagerDuty web UI does nested arrays/objects correctly. But the mobile app shows nested structures as strings.
