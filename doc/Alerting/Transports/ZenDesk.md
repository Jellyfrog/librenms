## Zenduty

Two options are available for ZenDuty support. The first, [native ZenDuty](#native-zenduty),
uses the API Transport, as given in the official [ZenDuty integration documentation](https://docs.zenduty.com/docs/librenms).
The other method uses a [native LibreNMS transport](#native-librenms-transport).

### Native ZenDuty
With the LibreNMS > Zenduty Integration, users can send new LibreNMS 
alerts to the correct team, and notify them from on-call schedules
through email, SMS, Phone Calls, Slack, Microsoft Teams and mobile push
notifications. Zenduty gives engineers detailed context for 
the LibreNMS alert, together with playbooks and a full incident command
framework, to examine, repair and resolve incidents quickly.

Create a [LibreNMS Integration](https://docs.zenduty.com/docs/librenms) in 
[Zenduty](https://www.zenduty.com). Then copy the Webhook URL from Zenduty
to LibreNMS.

For a detailed guide with screenshots, refer to the 
[LibreNMS documentation at Zenduty](https://docs.zenduty.com/docs/librenms).

**Example:**

| Config | Example |
| ------ | ------- |
| WebHook URL | <https://www.zenduty.com/api/integration/librenms/integration-key/> |

### Native LibreNMS Transport
This integration uses the [ZenDuty Webhooks](https://zenduty.com/docs/generic-integration/). 
With it, you can use all available ZenDuty parameters, such as URLs, SLA, 
Escalation Policies, and so on.

Do the steps in the link above to get your Webhook URL. Then paste it 
into the `ZenDuty WebHook` field when you set up the LibreNMS transport.

You can also set the SLA ID and the Escalation Policy ID in the Transport configuration. 
The system sends them with all alerts.

This transport sends these fields:

`message` - The alert title
`alert_type` - The severity of the alert rule, or acknowledged or resolved, as applicable to the state of the alert.
`entity_id` - The alert ID
`urls` - A link back to the device that causes the alert.
`summary` - The output of the template attached to the alert rule.

To adjust what the system sends to ZenDuty, and to replace or add fields, you can create 
a custom template that shows the correct information as JSON. As an example:

```json
{
    "message": "{{ $alert->title }}",
    "payload": {
        "sysName": "{{ $alert->sysName }}",
        "Device Type": "{{ $alert->type }}"
    },
    "summary": "Severity: {{ $alert->severity }}\nTimestamp: {{ $alert->timestamp }}\nRule: {{ $alert->title }}\n @foreach ($alert->faults as $key => $value) {{ $key }}: {{ $value['string'] }}\n @endforeach",
    "sla": "ccaf3fd6-db51-4f9f-818b-de42aee54f29",
    "urls": [
        {
            "link_url": "{{ route('device', ['device' => $alert->device_id ?: 1]) }}",
            "link_text": "{{ $alert->hostname }}"
        },
        {
            "link_url": "{{ route('device', ['device' => $alert->device_id ?? 1, 'tab' => 'alerts']) }}",
            "link_text": "{{ $alert->hostname }} - Alerts"
        }
    ]
}
```
If you are using more than one transport for an alert rule and need to customise the output per 
transport then you can do the following:

```
@if ($alert->transport == 'ZenDuty')
{
  "message": "{{ $alert->title }}",
  "payload": {
    "sysName": "{{ $alert->sysName }}",
    "Device Type": "{{ $alert->type }}"
  },
  "summary": "Severity: {{ $alert->severity }}\nTimestamp: {{ $alert->timestamp }}\nRule: {{ $alert->title }}\n @foreach ($alert->faults as $key => $value) {{ $key }}: {{ $value['string'] }}\n @endforeach",
  "sla": "ccaf3fd6-db51-4f9f-818b-de42aee54f29",
  "urls": [
    {
      "link_url": "{{ route('device', ['device' => $alert->device_id ?: 1]) }}",
      "link_text": "{{ $alert->hostname }}"
    },
    {
      "link_url": "{{ route('device', ['device' => $alert->device_id ?? 1, 'tab' => 'alerts']) }}",
      "link_text": "{{ $alert->hostname }} - Alerts"
    }
  ]
}
@else
{{ $alert->title }}
Severity: {{ $alert->severity }}
@if ($alert->state == 0) Time elapsed: {{ $alert->elapsed }} @endif
Timestamp: {{ $alert->timestamp }}
Unique-ID: {{ $alert->uid }}
Rule: @if ($alert->name) {{ $alert->name }} @else {{ $alert->rule }} @endif
@if ($alert->faults) Faults:
@foreach ($alert->faults as $key => $value)
  {{ $key }}: {{ $value['string'] }}
@endforeach
@endif
Alert sent to:
@foreach ($alert->contacts as $key => $value)
  {{ $value }} <{{ $key }}>
@endforeach
@endif
```

| Config               | Example                                                      |
|----------------------|--------------------------------------------------------------|
| WebHook URL          | <https://events.zenduty.com/integration/we8jv/generic/hash/> |
| SLA ID               | g27u4gr824r-dd32rf2wdedeas-3e2wd223d23                       |
| Escalation Policy ID | KIJDi23rwnef23-dankjd323r-DSAD£2232fds                        |