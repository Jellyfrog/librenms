# Templates

You can attach templates to one rule or to a group of rules. Templates
can contain each type of text. There is also a default template. The
system uses it for each rule that has no attached template. This
template is on the `Alert Templates` page, and you can edit it. There
is also an option to set it back to its default content.

To attach a template to a rule, open the `Alert Templates`
settings page. Select the applicable template and click the yellow
button in the `Actions` column. A popup box opens. In it, select the
rule(s) to which you want to attach the template, and click the `Attach`
button. You can hold the CTRL key to select multiple rules at the same time.

!!! note
    A rule can have only one attached template at a time.

Alert templates are based on Laravel Blade. We give some of
the basics here. The official Laravel docs have more
information [here](https://laravel.com/docs/blade).

!!! warning
    Laravel blade permits @php, which can read/write local files,
    run database queries and more. If you do not trust your users, do not give them
    access to create or edit templates.

## Syntax

Controls:

- if-else (You can omit Else): `@if ($alert->placeholder  ==
  'value') Some Text @else Other Text @endif`
- foreach-loop: `@foreach ($alert->faults as $key => $value) Key: $key Value: $value @endforeach`

Placeholders:

Placeholders are special variables. When you use them in the template,
the system replaces them with the applicable data. For example:

`The device {{ $alert->hostname }} has been up for {{ $alert->uptime
}} seconds` gives this result: `The device localhost has
been up for 30344 seconds`.

!!! note
    When you use placeholders to show data, you must put
    the placeholder in `{{ }}`. That is, `{{ $alert->hostname }}`.

- Device ID: `$alert->device_id`
- Hostname of the Device: `$alert->hostname`
- sysName of the Device: `$alert->sysName`
- sysDescr of the Device: `$alert->sysDescr`
- display name of the Device: `$alert->display`
- sysContact of the Device: `$alert->sysContact`
- OS of the Device: `$alert->os`
- Type of Device: `$alert->type`
- IP of the Device: `$alert->ip`
- Hardware of the Device: `$alert->hardware`
- Software version of the Device: `$alert->version`
- Features of the Device: `$alert->features`
- Serial number of the Device: `$alert->serial`
- Location of the Device: `$alert->location`
- Device Groups of the Device (group_id->group_name Array): `$alert->device_groups`
- uptime of the Device (in seconds): `$alert->uptime`
- Short uptime of the Device (28d 22h 30m 7s): `$alert->uptime_short`
- Long uptime of the Device (28 days, 22h 30m 7s): `$alert->uptime_long`
- Description (purpose db field) of the Device: `$alert->description`
- Notes of the Device: `$alert->notes`
- Notes of the alert (ack notes): `$alert->alert_notes`
- ping timestamp (if icmp enabled): `$alert->ping_timestamp`
- ping loss (if icmp enabled): `$alert->ping_loss`
- ping min (if icmp enabled): `$alert->ping_min`
- ping max (if icmp enabled): `$alert->ping_max`
- ping avg (if icmp enabled): `$alert->ping_avg`
- debug (array) 
- Title for the Alert: `$alert->title`
- Time Elapsed, Only available on recovery (`$alert->state == 0`): `$alert->elapsed`
- Rule Builder (the actual rule) (use `{!! $alert->builder !!}`): `$alert->builder`
- Alert-ID: `$alert->id`
- Unique-ID: `$alert->uid`
- Faults, available only on alert (`$alert->state != 0`). You must go
  through it in a foreach (`@foreach ($alert->faults as $key => $value)
  @endforeach`). It holds all available information about the Fault.
  You get access in the format `$value['Column']`, for example:
  `$value['ifDescr']`. The special field `$value['string']` has most
  Identification-information (IDs, Names, Descrs) as a single string.
  This is the equivalent of the default. You must put it in `{{ }}`
- State: `$alert->state`
- Severity: `$alert->severity`
- Rule-Name: `$alert->name`
- Procedure URL: `$alert->proc`
- Timestamp: `$alert->timestamp`
- Transport type: `$alert->transport`
- Transport name: `$alert->transport_name`
- Contacts. You must go through it in a foreach. `$key` holds the email and
  `$value` holds the name: `$alert->contacts`
- Application Data: `$alert->applications`
- Application Metrics: `$alert->applications_metrics`

You can also use placeholders in the subjects for templates. But
$faults is usually not useful there, because it is
an array.

The Default Template is a 'one-size-fit-all'. We strongly recommend
that you define your own templates for your rules, to include more
specific information.

## Base Templates

If you want to use a common template for your alerts, you can
create your own template (a default is included).

The default file is in
`resources/views/alerts/templates/default.blade.php`
and shows this:

```php
<html>
    <head>
        <title>LibreNMS Alert</title>
    </head>
    <body>
        <div class="container">
            @yield('content')
        </div>
    </body>
</html>
```

The important part is the `@yield('content')`

You can use plain text or html, the same as in Alert templates. This
is the base of your common template. You can make as many
templates in the directory as necessary.

In your alert template, use

```php
@extends('alerts.templates.default')

@section('content')
  {{ $alert->title }}
  Severity: {{ $alert->severity }}
  ...
@endsection
```

For more info on extending templates, see the [Laravel documentation](https://laravel.com/docs/blade#extending-a-layout).

### Including other Alert templates

A different method to extend a template is to use the content of other Alert templates in LibreNMS. To do this, use the AlertTemplate database model. You must pass all variables that the included template needs through the second parameter (for example ```["alert" => $alert]```) of the method Blade:render(). 
The example below includes the full content of the template with the ID 5.  This is useful to keep all common text parts in separate templates. For example, headers or footers.
```php
{ \Illuminate\Support\Facades\Blade::render(\App\Models\AlertTemplate::find(5)->template , ["alert" => $alert]) }}
```

## Examples

### Default Template

```php
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
```

#### Ports Utilization Template

```php
{{ $alert->title }}
Device Name: {{ $alert->hostname }}
Severity: {{ $alert->severity }}
@if ($alert->state == 0) Time elapsed: {{ $alert->elapsed }} @endif
Timestamp: {{ $alert->timestamp }}
Rule: @if ($alert->name) {{ $alert->name }} @else {{ $alert->rule }} @endif
@foreach ($alert->faults as $key => $value)
Physical Interface: {{ $value['ifDescr'] }}
Interface Description: {{ $value['ifAlias'] }}
Interface Speed: {{ ($value['ifSpeed']/1000000000) }} Gbs
Inbound Utilization: {{ (($value['ifInOctets_rate']*8)/$value['ifSpeed'])*100 }}
Outbound Utilization: {{ (($value['ifOutOctets_rate']*8)/$value['ifSpeed'])*100 }}
@endforeach
```

#### Storage

```php
{{ $alert->title }}

Device Name: {{ $alert->hostname }}
Severity: {{ $alert->severity }}
Uptime: {{ $alert->uptime_short }}
@if ($alert->state == 0) Time elapsed: {{ $alert->elapsed }} @endif
Timestamp: {{ $alert->timestamp }}
Location: {{ $alert->location }}
Description: {{ $alert->description }}
Features: {{ $alert->features }}
Notes: {{ $alert->notes }}

Server: {{ $alert->sysName }}
@foreach ($alert->faults as $key => $value)
Mount Point: {{ $value['storage_descr'] }}
Percent Utilized: {{ $value['storage_perc'] }}
@endforeach
```

#### Value Sensors (Temperature, Humidity, Fanspeed, ...)

```php
{{ $alert->title }}

Device Name: {{ $alert->hostname }}
Severity: {{ $alert->severity }}
Timestamp: {{ $alert->timestamp }}
Uptime: {{ $alert->uptime_short }}
@if ($alert->state == 0)
Time elapsed: {{ $alert->elapsed }}
@endif
Location: {{ $alert->location }}
Description: {{ $alert->description }}
Features: {{ $alert->features }}
Notes: {{ $alert->notes }}

Rule: {{ $alert->name ?? $alert->rule }}
@if ($alert->faults)
Faults:
@foreach ($alert->faults as $key => $value)
@php($unit = __("sensors.${value["sensor_class"]}.unit"))
#{{ $key }}: {{ $value['sensor_descr'] ?? 'Sensor' }}

Current: {{ $value['sensor_current'].$unit }}
Previous: {{ $value['sensor_prev'].$unit }}
Limit: {{ $value['sensor_limit'].$unit }}
Over Limit: {{ round($value['sensor_current']-$value['sensor_limit'], 2).$unit }}

@endforeach
@endif
```

#### Memory Alert

```php
{{ $alert->title }}

Device Name: {{ $alert->hostname }}
Severity: {{ $alert->severity }}
Uptime: {{ $alert->uptime_short }}
@if ($alert->state == 0) Time elapsed: {{ $alert->elapsed }} @endif
Timestamp: {{ $alert->timestamp }}
Location: {{ $alert->location }}
Description: {{ $alert->description }}
Notes: {{ $alert->notes }}

Server: {{ $alert->hostname }}
@foreach ($alert->faults as $key => $value)
Memory Description: {{ $value['mempool_descr'] }}
Percent Utilized: {{ $value['mempool_perc'] }}
@endforeach
```

#### Sneck Alert

```text
{{ $alert->title }}
Severity: {{ $alert->severity }}
@if ($alert->state == 0) Time elapsed: {{ $alert->elapsed }} @endif
Timestamp: {{ $alert->timestamp }}
Unique-ID: {{ $alert->uid }}
@if ($alert->description) Description: {{ $alert->description }} @endif
@if ($alert->notes) Notes: {{ $alert->notes }} @endif
Alert String: {{ $alert->applications['sneck'][0]['data']['alertString'] }}
```

### Advanced options

#### Conditional formatting

Conditional formatting example. It shows a link to the host in
email, or only the hostname in each other transport:

```php
@if ($alert->transport == 'mail')<a href="https://my.librenms.install/device/device={{ $alert->hostname }}/">{{ $alert->hostname }}</a>
@else
{{ $alert->hostname }}
@endif
```

#### Traceroute debugs

```php
@if ($alert->status == 0)
    @if (str_contains((string) $alert->status_reason, 'icmp'))
        {{ $alert->debug['traceroute'] }}
    @endif
@endif
```

### Using Application Data In Alert Templates

You can use application data in an alert template. `$alert->applications` is an
associative array that contains the applications for the device
that the alert applies to. Each sub array contains that line from the
applications table. For example, to get access to the app data for Sneck,
use `$alert->applications['sneck'][0]['data']`. Thus, to
use the value `.data.alertString` in the stored return JSON, we
use `$alert->applications['sneck'][0]['data']['data']['alertString']`.

To see what is usable, call
`lnms report:devices -o json -r applications $device | jq -S .applications | less`
on a device that has the applicable app. Examine
the app data section.

`[0]` is there because the legacy apps proxmox and drdb do not
use app data, and can have multiple instances.

#### Metrics

Application metrics are also available through `$alert->application_metrics`.

For example, for ZFS, to include error info, you can do this.

```
Current Total Errors: {{ $alert->applications['zfs'][0]['total_errors']['value'] }}
Current Read Errors: {{ $alert->applications['zfs'][0]['read_errors']['value'] }}
Current Write Errors: {{ $alert->applications['zfs'][0][write_errors']['value'] }}

Previous Total Errors: {{ $alert->applications['zfs'][0]['total_errors']['value_prev'] }}
Previous Read Errors: {{ $alert->applications['zfs'][0]['read_errors']['value_prev'] }}
Previous Write Errors: {{ $alert->applications['zfs'][0][write_errors']['value_prev'] }}
```

## Examples HTML

To use HTML emails you must set HTML email to Yes in the WebUI:

!!! setting "alerting/email"
    ```bash
    lnms config:set email_html true
    ```

## Graphs

There are two helpers for graphs. They use a signed url for safe external
access. Each person who has the signed url can see the graph.

 - Your LibreNMS web must be accessible from the location where the graph is viewed.
   Some alert transports need publicly accessible urls.
 - APP_URL must be set in .env to use signed graphs.
 - A change of APP_KEY makes all signed urls from before invalid.

You can specify the graph in one of two ways: a php array of parameters, or
a direct url to a graph.

You can specify to and from as timestamps with `time()`,
or as relative time `-3d` or `-36h`.  With relative time, the graph
shows data from the time when the user sees the graph, not the time when the event occurred.
When you share a graph image with a relative time, the receiver always gets access
to current data. With a specified timestamp, access is only to that timeframe.

### @signedGraphTag

This puts in a specially formatted html img tag with a link to the graph.
Some transports look for this tag in the template, to attach images correctly
for that transport.

```php
@signedGraphTag([
    'id' => $value['port_id'],
    'type' => 'port_bits',
    'from' => time() - 43200,
    'to' => time(),
    'width' => 700, 
    'height' => 250
])
```

Output:

```html
<img class="librenms-graph" src="https://librenms.org/graph?from=1662176216&amp;height=250&amp;id=20425&amp;to=1662219416&amp;type=port_bits&amp;width=700&amp;signature=f6e516e8fd893c772eeaba165d027cb400e15a515254de561a05b63bc6f360a4">
```

Specific graph using url input:

```php
@signedGraphTag('https://librenms.org/graph.php?type=device_processor&from=-2d&device=2&legend=no&height=400&width=1200')
```

### @signedGraphUrl

Use this when you need the url directly. One example is the
API Transport. There, you possibly want to include only the url, not a html tag.

```php
@signedGraphUrl([
    'id' => $value['port_id'],
    'type' => 'port_bits',
    'from' => time() - 43200,
    'to' => time(),
])
```

## Using models for optional data

If a value does not exist in the `$faults[]` array, you can
query fields from the database with Laravel models. You can use
models to query more values and use them in the template. Put
the model and the value to look for in the braces. For
example, ISIS alerts have a `port_id` value attached to the
alert, but `ifName` is not directly accessible from the
`$faults[]` array. If you need the name of the port, you can
query its value with a template such as:

```php
{{ $alert->title }}
Severity: {{ $alert->severity }}
@if ($alert->state == 0) Time elapsed: {{ $alert->elapsed }} @endif
Timestamp: {{ $alert->timestamp }}
Rule: @if ($alert->name) {{ $alert->name }} @else {{ $alert->rule }} @endif
@if ($alert->faults) Faults:
@foreach ($alert->faults as $key => $value)
  Local interface: {{ \App\Models\Port::find($value['port_id'])->ifName }}
  Adjacent IP: {{ $value['isisISAdjIPAddrAddress'] }}
  Adjacent state: {{ $value['isisISAdjState'] }}
@endforeach
@endif
```

### Service Alert

```php
<div style="font-family:Helvetica;">
<h2>@if ($alert->state == 1) <span style="color:red;">{{ $alert->severity }} @endif
@if ($alert->state == 2) <span style="color:goldenrod;">acknowledged @endif</span>
@if ($alert->state == 3) <span style="color:green;">recovering @endif</span>
@if ($alert->state == 0) <span style="color:green;">recovered @endif</span>
</h2>
<b>Host:</b> {{ $alert->hostname }}<br>
<b>Duration:</b> {{ $alert->elapsed }}<br>
<br>

@if ($alert->faults)
@foreach ($alert->faults as $key => $value) <b>{{ $value['service_desc'] }} - {{ $value['service_type'] }}</b><br>
{{ $value['service_message'] }}<br>
<br>
@endforeach
@endif
</div>
```

#### Processor Alert with Graph

```php
{{ $alert->title }} <br>
Severity: {{ $alert->severity }}  <br>
@if ($alert->state == 0) Time elapsed: {{ $alert->elapsed }} @endif
Timestamp: {{ $alert->timestamp }} <br>
Alert-ID: {{ $alert->id }} <br>
Rule: @if ($alert->name) {{ $alert->name }} @else {{ $alert->rule }} @endif <br>
@if ($alert->faults) Faults:
@foreach ($alert->faults as $key => $value)
{{ $key }}: {{ $value['string'] }}<br>
@endforeach
@if ($alert->faults) <b>Faults:</b><br>
@foreach ($alert->faults as $key => $value)
@signedGraphTag(['device' => $value['device_id'], 'type' => 'device_processor', 'width' => 459, 'height' => 213, 'from' => time() - 259200])<br>
https://server/graphs/device={{ $value['device_id'] }}/type=device_processor/<br>
@endforeach
Template: CPU alert <br>
@endif
@endif
```

## Included

We include some templates for you to use. These apply to specified
types of alert rules. For example, if you create a rule
that sends alerts for BGP sessions, you can attach the BGP template
to this rule to give more information.

The included templates, in addition to the default template, are:

- BGP Sessions
- Ports
- Temperature

## Other Examples

### Microsoft Teams - Markdown

```php
[{{ $alert->title }}](https://your.librenms.url/device/device={{ $alert->device_id }}/)
**Device name:** {{ $alert->sysName }}
**Severity:** {{ $alert->severity }}
@if ($alert->state == 0)
**Time elapsed:** {{ $alert->elapsed }}
@endif
**Timestamp:** {{ $alert->timestamp }}
**Unique-ID:** {{ $alert->uid }}
@if ($alert->name)
**Rule:** {{ $alert->name }}
@else
**Rule:** {{ $alert->rule }}
@endif
@if ($alert->faults)
**Faults:**@foreach ($alert->faults as $key => $value) {{ $key }}: {{ $value['string'] }}
@endforeach
@endif
```

### Microsoft Teams - JSON

```php
{
    "@@context": "https://schema.org/extensions",
    "@type": "MessageCard",
    "title": "{{ $alert->title }}",
@if ($alert->state === 0)
    "themeColor": "00FF00",
@elseif ($alert->state === 1)
    "themeColor": "FF0000",
@elseif ($alert->state === 2)
    "themeColor": "337AB7",
@elseif ($alert->state === 3)
    "themeColor": "FF0000",
@elseif ($alert->state === 4)
    "themeColor": "F0AD4E",
@else
    "themeColor": "337AB7",
@endif
    "summary": "LibreNMS",
    "sections": [
        {
@if ($alert->name)
            "facts": [
                {
                    "name": "Rule:",
                    "value": "[{{ $alert->name }}](https://your.librenms.url/device/device={{ $alert->device_id }}/tab=alert/)"
                },
@else
                {
                    "name": "Rule:",
                    "value": "[{{ $alert->rule }}](https://your.librenms.url/device/device={{ $alert->device_id }}/tab=alert/)"
                },
@endif
                {
                    "name": "Severity:",
                    "value": "{{ $alert->severity }}"
                },
                {
                    "name": "Unique-ID:",
                    "value": "{{ $alert->uid }}"
                },
                {
                    "name": "Timestamp:",
                    "value": "{{ $alert->timestamp }}"
                },
@if ($alert->state == 0)
                {
                    "name": "Time elapsed:",
                    "value": "{{ $alert->elapsed }}"
                },
@endif
                {
                    "name": "Hostname:",
                    "value": "[{{ $alert->hostname }}](https://your.librenms.url/device/device={{ $alert->device_id }}/)"
                },
                {
                    "name": "Hardware:",
                    "value": "{{ $alert->hardware }}"
                },
                {
                    "name": "IP:",
                    "value": "{{ $alert->ip }}"
                },
                {
                    "name": "Faults:",
                    "value": " "
                }
            ]
@if ($alert->faults)
@foreach ($alert->faults as $key => $value)
        },
        {
            "facts": [
                {
                    "name": "Port:",
                    "value": "[{{ $value['ifName'] }}](https://your.librenms.url/device/device={{ $alert->device_id }}/tab=port/port={{ $value['port_id'] }}/)"
                },
                {
                    "name": "Description:",
                    "value": "{{ $value['ifAlias'] }}"
                },
@if ($alert->state != 0)
                {
                    "name": "Status:",
                    "value": "down"
                }
            ]
@else
                {
                    "name": "Status:",
                    "value": "up"
                }
            ]
@endif
@endforeach
@endif
        }
    ]
}
```

### Microsoft Teams - AdaptiveCard JSON

```php
@php
    $state_color = match ((int) $alert->state) {
        0  => 'Good',       // CLEAR, RECOVERED
        1  => 'Attention',  // ACTIVE
        2  => 'Accent',     // ACKNOWLEDGED
        3  => 'Attention',  // WORSE
        4  => 'Warning',    // BETTER
        5  => 'Warning',    // CHANGED
        default => 'Default',
    };
    $severity_color = match ($alert->severity) {
        'ok', 'Ok' => 'Good',
        'warning', 'Warning' => 'Warning',
        'critical', 'Critical' => 'Attention',
        default => 'Default',
    };
@endphp
{
    "type": "message"
    "attachments": [
        {
            "contentType": "application/vnd.microsoft.card.adaptive",
            "content": {
                "$schema": "http://adaptivecards.io/schemas/adaptive-card.json",
                "version": "1.4",
                "type": "AdaptiveCard",
                "body": [
                    {
                        "type":  "TextBlock",
                        "size":  "Large",
                        "weight":  "Bolder",
                        "color":  "{{ $state_color }}",
                        "text":  "🚨 **LibreNMS Alert @if ($alert->state == 0) - Resolved @endif**",
                        "horizontalAlignment":  "Center",
                        "spacing":  "Small"
                    },
                    {
                        "type":  "TextBlock",
                        "text":  "**🔔** {{ $alert->title }}",
                        "wrap":  true,
                        "color": "Accent",
                        "weight":  "Bolder",
                        "spacing":  "Small"
                    },
                    {
                        "type":  "TextBlock",
                        "text":  "**📌 State:** @switch ($alert->state)
                            @case (0) OK ✅ @break
                            @case (1) Warning ⚠️ @break
                            @case (2) Critical ❌ @break
                            @default Unknown @endswitch",
                        "wrap":  true,
                        "color":  "{{ $state_color }}",
                        "spacing":  "Small"
                    },
                    @if ($alert->state == 0) {
                        "type":  "TextBlock",
                        "text":  "**🕒 Elapsed:** {{ $alert->elapsed }}",
                        "wrap":  true,
                        "spacing":  "Small"
                    }, @endif
                    {
                        "type":  "TextBlock",
                        "text":  "**📅 Timestamp:** {{ $alert->timestamp }}",
                        "wrap":  true,
                        "spacing":  "Small"
                    },
                    {
                        "type":  "TextBlock",
                        "text":  "**🆔 Unique-ID:** {{ $alert->uid }}",
                        "wrap":  true,
                        "spacing":  "Small"
                    },
                    {
                        "type":  "TextBlock",
                        "text":  "**⚠️ Severity:**  {{ $alert->severity }}",
                        "wrap":  true,
                        "color":  "{{ $severity_color }}",
                        "spacing":  "Small"
                    },
                    {
                        "type":  "TextBlock",
                        "text":  "**📜 Rule:**  @if ($alert->name) {{ $alert->name }} @else {{ $alert->rule }} @endif",
                        "wrap":  true,
                        "color":  "Accent",
                        "spacing":  "Small"
                    },
                    @if ($alert->faults and count($alert->faults) > 0)
                    {
                        "type":  "TextBlock",
                        "text":  "**🔍 Fault Details:**",
                        "wrap":  true,
                        "size":  "Medium",
                        "weight":  "Bolder",
                        "spacing":  "Small"
                    },
                    @foreach ($alert->faults as $fault_key => $fault_details)
                    {
                        "type": "ActionSet",
                        "actions": [
                            {
                                "type": "Action.ShowCard",
                                "title": "Fault {{ $fault_key }} ",
                                "card": {
                                    "type": "AdaptiveCard",
                                    "body": [
                                        {
                                            "type":  "FactSet",
                                            "separator":  true,
                                            "facts":  [
                                                @foreach ($fault_details as $key => $value)
                                                @if ($key == 'string')
                                                    {{--
                                                        the 'string' key is a redundant amalgam of all 
                                                        other keys in the assoc array, skip it
                                                    --}}
                                                    @continue    
                                                @endif
                                                {
                                                    "title":  "{{ $key }}",
                                                    "value":  "{{ str_replace(array("\r\n", "\n", "\r"), "", $value) }}"
                                                },
                                                @endforeach
                                                {"title": "", "value": ""}
                                            ]
                                        }
                                    ]
                                }
                            }
                        ]
                    },
                    @endforeach
                    {"type": "TextBlock", "text": ""}
                    @else
                    {"type": "TextBlock", "text": "No fault data in this alert"}
                    @endif
                ],
                "actions":  [
                    {
                        "type":  "Action.OpenUrl",
                        "title":  "View Alert",
                        "style": "positive",
                        "url":  "https://librenms.server.utsc.utoronto.ca/device/{{ $alert->device_id }}/alerts"
                    }
                ]
                }
        }
    ]
}
```
