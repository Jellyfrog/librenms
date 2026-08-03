## Grafana Oncall

Send alerts to Grafana Oncall through a Formatted Webhook or a Webhook.
[See the Grafana documentation for both](https://grafana.com/docs/oncall/latest/integrations/webhook/).

The difference between the two is small. But the Formatted Webhook 
gives an easier view by default.

> NOTE: By default, Grafana converts acknowledged alerts to resolved alerts.
> To change this, update the Template settings for the integration that you
> added, as follows.

Autoresolution: `{{ payload.get("raw_state", "") != 2 and payload.get("state", "").upper() == "OK" }}`

Auto acknowledge: `{{ payload.get("raw_state", "") == 2 }}`

The payload to Grafana also contains more information, which 
can be useful in the templates or routes. If you do a test of the LibreNMS transport, 
you can see the payload in the Grafana interface.

To adjust what the system sends to Grafana, and to replace or add fields, you can create
a custom template that shows the correct information as JSON. As an example:

```
{
    "message": "Severity: {{ $alert->severity }}\nTimestamp: {{ $alert->timestamp }}\nRule: {{ $alert->title }}\n @foreach ($alert->faults as $key => $value) {{ $key }}: {{ $value['string'] }}\n @endforeach",
    "number_of_processors": \App\Models\Processors::where('device_id', $alert->device_id)->count(),
    "title": "{{ $alert->title }}",
    "link_to_upstream_details": "{{ \LibreNMS\Util\Url::deviceUrl($device) }}",
}
```
If you use more than one transport for an alert rule, and you must adjust the output for each
transport, you can do this:

```
@if ($alert->transport == 'grafana')
{
  "message": "Severity: {{ $alert->severity }}\nTimestamp: {{ $alert->timestamp }}\nRule: {{ $alert->title }}\n @foreach ($alert->faults as $key => $value) {{ $key }}: {{ $value['string'] }}\n @endforeach",
  "number_of_processors": \App\Models\Processors::where('device_id', $alert->device_id)->count(),
  "title": "{{ $alert->title }}",
  "link_to_upstream_details": "{{ \LibreNMS\Util\Url::deviceUrl($device) }}",
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

**Example:**

| Config | Example |
| ------ | ------- |
| Webhook URL | https://a-prod-us-central-0.grafana.net/integrations/v1/formatted_webhook/m12xmIjOcgwH74UF8CN4dk0Dh/ |