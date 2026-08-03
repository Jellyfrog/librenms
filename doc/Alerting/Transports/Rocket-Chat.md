## Rocket.chat

The Rocket.chat transport POSTs the alert message to your
Rocket.chat Incoming WebHook with the attachments option. The system
removes simple html tags from the message. All options are optional. The only
mandatory value is the url. Without it, the system makes no call to Rocket.chat.

[Rocket.chat Docs](https://rocket.chat/docs/developer-guides/rest-api/chat/postmessage)

**Example:**

| Config | Example |
| ------ | ------- |
| Webhook URL | https://rocket.url/api/v1/chat.postMessage |
| Rocket.chat Options | channel=#Alerting <br/> username=myname <br/> icon_url=http://someurl/image.gif <br/> icon_emoji=:smirk: |