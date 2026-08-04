## API

With the API transport, you can get access to each service provider that uses POST, PUT or GET URLs
(such as an SMS provider, and so on). You can use it in multiple ways:

- The same text built from the Alert template is available in the
  variable

`$msg`, which you can then send as an option to the API. Be careful:
HTTP GET requests usually have a length limit.

- You can build the API-Option fields directly from the variables
  in [Template-Syntax](../Templates.md#syntax), but without the
  'alert->' prefix. For example, `$alert->uptime` is available as
  `$uptime` in the API transport

- With the API-Headers, you can add the headers that the api endpoint needs.

- With the API-body, you can send data in the format that the API endpoint needs.

- Send as form. With this option, you can send the body content as form data, url encoded. Enable this if your endpoint expects fields as key=value pairs. Make sure that there are no newlines in your variables. Newlines can occur, for example, in `$msg`.

Some frequently used variables :

| Variable            | Description |
| ------------------  | ----------- |
| {{ $hostname }}     | Hostname |
| {{ $sysName }}      | SysName |
| {{ $sysDescr }}     | SysDescr |
| {{ $os }}           | OS of device (librenms defined) |
| {{ $type }}         | Type of device (librenms defined) |
| {{ $ip }}           | IP Address |
| {{ $hardware }}     | Hardware |
| {{ $version }}      | Version |
| {{ $uptime }}       | Uptime in seconds |
| {{ $uptime_short }} | Uptime in human-readable format |
| {{ $timestamp }}    | Timestamp of alert |
| {{ $description }}  | Description of device |
| {{ $title }}        | Title (as built from the Alert Template) |
| {{ $msg }}          | Body text (as built from the Alert Template) |

**Example:**

The example below uses the API with the name sms-api of my.example.com. It sends
the title of the alert to the given number, with the given service key.
Refer to your service documentation to configure it correctly.

| Config | Example |
| ------ | ------- |
| API Method    | GET |
| API URL       | <http://my.example.com/sms-api>
| API Options   | rcpt=0123456789 <br/> key=0987654321abcdef <br/> msg=(LNMS) {{ $title }} |
| API Username  | myUsername |
| API Password  | myPassword |

The example below uses the API with the name wall-display of my.example.com. It sends
the title and the text of the alert to a screen in the Network Operation Center.

| Config | Example |
| ------ | ------- |
| API Method    | POST |
| API URL       | <http://my.example.com/wall-display>
| API Options   | title={{ $title }} <br/> msg={{ $msg }}|

The example below uses the API with the name component of my.example.com,
with id 1. The body is a json status value. The headers send the necessary token
authentication and content type.

| Config | Example |
| ------ | ------- |
| API Method    | PUT |
| API URL       | http://my.example.com/comonent/1
| API Headers   | X-Token=HASH
|               | Content-Type=application/json
| API Body      | { "status": 2 }