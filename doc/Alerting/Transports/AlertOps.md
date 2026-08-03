## AlertOps

With the AlertOps integration, LibreNMS can send alerts to AlertOps with detailed information. AlertOps operates as a dispatcher for LibreNMS alerts. With it, you can find the correct persons or teams to notify, from on-call schedules. AlertOps can send notifications through different channels, which include email, text messages (SMS), phone calls, and mobile push notifications for iOS & Android devices. AlertOps also supplies escalation policies. These make sure that alerts are managed correctly until they are assigned or closed. You can also remove or collect alerts, from different values.

To set up the integration:

- Create a LibreNMS Integration: Make an AlertOps account and create a LibreNMS integration from the integrations page. This makes an Inbound Integration Endpoint URL. You must copy this URL to LibreNMS.

- Configure LibreNMS Integration: In LibreNMS, go to the integration settings and paste the inbound integration URL that you got from AlertOps.

**Example:**

| Config | Example |
| ------ | ------- |
| WebHook URL | <https://url/path/to/webhook> |
