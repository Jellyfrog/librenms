## OpsGenie

> ⚠️ **Atlassian announced the EOL of Opsgenie on the 5th April 2027.
[Read more here](https://www.atlassian.com/blog/announcements/evolution-of-it-operations)

With the OpsGenie LibreNMS integration, LibreNMS sends alerts to
OpsGenie with detailed information. OpsGenie operates as a dispatcher for
LibreNMS alerts. It finds the correct persons to notify, from
on-call schedules, and sends notifications through email, text messages (SMS), phone
calls and iOS & Android push notifications. Then it escalates alerts
until the alert is acknowledged or closed.

After you make an account, create a [LibreNMS
Integration](https://docs.opsgenie.com/docs/librenms-integration) from
the integrations page. Then copy the API key from OpsGenie to LibreNMS.

If you want to automatically ack and close alerts, use the Marid
integration. More detail, with screenshots, is available on the
[OpsGenie LibreNMS Integration page](https://docs.opsgenie.com/docs/librenms-integration).

**Example:**

| Config | Example |
| ------ | ------- |
| WebHook URL | <https://url/path/to/webhook> |