## Jira Service Management

With the Jira Service Management LibreNMS integration, LibreNMS sends alerts to
Jira Service Management with detailed information. Jira Service Management operates as a dispatcher for
LibreNMS alerts. It finds the correct persons to notify, from
on-call schedules, and sends notifications through email, text messages (SMS), phone
calls and iOS & Android push notifications. Then it escalates alerts
until the alert is acknowledged or closed.

:warning: If the feature is not available on your site, examine Jira Service Management for updates at regular intervals.

**Example:**

| Config | Example |
| ------ | ------- |
| WebHook URL | <https://url/path/to/webhook> |