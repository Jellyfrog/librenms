## Sensu

The Sensu transport POSTs an
[Event](https://docs.sensu.io/sensu-go/latest/reference/events/) to the
[Agent API](https://docs.sensu.io/sensu-go/latest/reference/agent/#create-monitoring-events-using-the-agent-api)
when an alert starts.

The system puts the event in a category (ok, warning or critical). If you configure the
alert to send recovery notifications, Sensu also clears the alert
automatically. No configuration is necessary. When the
Sensu Agent runs on your poller, with the HTTP socket enabled on tcp/3031, LibreNMS
starts to make Sensu events as soon as you create the transport.

Acknowledgement of alerts in LibreNMS is not directly supported. But the
system sets an annotation (`acknowledged`). Thus, you can write a mutator or silence, or the
handler, to look for it directly in the handler. The system also
sets an annotation (`generated-by`). With it, you can process LibreNMS events
differently from agent events.

The 'shortname' option is a simple method to decrease the length of device names in
configs. It replaces the last 3 domain components with single letters (e.g.
websrv08.dc4.eu.corp.example.net becomes websrv08.dc4.eu.cen).

### Limitations

- Only one namespace is supported
- Sensu does not accept rules with special characters - the Transport tries
to correct rule names, but we recommend only letters, numbers and spaces
- The transport uses only absolute states - it ignores the got worse/got better
/changed states
- The agent buffers alerts, but LibreNMS does not - if your agent is
offline, alerts are lost
- There is no backchannel between Sensu and LibreNMS - if you make changes in
Sensu to LibreNMS alerts, they are lost at the next event (silences operate correctly)

**Example:**

| Config          | Example               |
| --------------- | --------------------- |
| Sensu Endpoint  | http://localhost:3031 |
| Sensu Namespace | eu-west               |
| Check Prefix    | lnms                  |
| Source Key      | hostname              |