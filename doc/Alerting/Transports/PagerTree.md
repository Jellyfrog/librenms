## PagerTree

The PagerTree transport POSTs the alert message to your PagerTree
Incoming WebHook. The only mandatory value is the PagerTree webhook integration URL.

The PagerTree transport maps these fields from LibreNMS to PagerTree.  The system converts LibreNMS alert states to the PagerTree event type.

| LibreNMS alert state | PagerTree event_type |
| -------------------- | -------------------- |
| 0 (OK) | resolved |
| 1 (Alert) | create |
| 2 (Ack) | acknowledged |


| LibreNMS | PagerTree |
| -------- | --------- |
| Alert state | event_type |
| Alert ID | Id |
| Alert title | Title |
| Alert msg | Description |


To add the webhook in the PagerTree portal, select "Integrations" --> "New Integration" --> "webhooks".  The Webhook URL has the label "Endpoint" on the new PagerTree Integration summary page.

[PagerTree Docs](https://pagertree.com/docs/integration-guides/webhook). 
[LibreNMS Alert Data](https://github.com/librenms/librenms/blob/master/LibreNMS/Alert/AlertData.php).
