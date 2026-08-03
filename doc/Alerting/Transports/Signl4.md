## SIGNL4

SIGNL4 supplies critical alerting, incident response and service dispatching for the operation of critical infrastructure. It sends alerts to you continuously through app push, SMS text, voice calls, and email. It includes tracking, escalation, on-call duty scheduling and collaboration.

Connect SIGNL4 with LibreNMS to send critical alerts, with detailed information, to responsible persons or on-call teams. The integration can start alerts and also close them.

In the configuration for your SIGNL4 alert transport, you must only enter your SIGNL4 webhook URL, which includes the team or integration secret.

**Example:**

| Config | Example |
| ------ | ------- |
| Webhook URL | https://connect.signl4.com/webhook/{team-secret} |

More information about the integration is [here](https://docs.signl4.com/integrations/librenms/librenms.html).