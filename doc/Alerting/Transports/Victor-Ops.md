## VictorOps

VictorOps gives a webHook url that makes the integration very
simple. To get the necessary URL, log in to your VictorOps account and go to:

Settings -> Integrations -> REST Endpoint -> Enable Integration.

The URL has $routing_key at the end. You must change
this to a value that is unique to the system that sends the alerts,
such as librenms. I.e:

`https://alert.victorops.com/integrations/generic/20132414/alert/2f974ce1-08fc-4dg8-a4f4-9aee6cf35c98/librenms`

**Example:**

| Config | Example |
| ------ | ------- |
| Post URL | <https://alert.victorops.com/integrations/generic/20132414/alert/2f974ce1-08fc-4dg8-a4f4-9aee6cf35c98/librenms> |