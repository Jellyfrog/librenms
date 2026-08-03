## Slack

The Slack transport POSTs the alert message to your Slack Incoming
WebHook with the attachments option. You can specify multiple
webhooks, together with the applicable options for each. The system
removes simple html tags from the message. All options are optional. The
only mandatory value is the url. Without it, the system makes no call to Slack.

At this time, we support these attachment options:

- `author_name`

At this time, we support these global message options:

- `channel_name` : Slack channel name (without the '#' at the start) to which the alert goes
- `icon_emoji` : Emoji name in colon format to use as the author icon

[Slack docs](https://api.slack.com/docs/message-attachments)

The alert template can use
[Slack markdown](https://api.slack.com/reference/surfaces/formatting#basic-formatting).
In the Slack markdown dialect, custom links use HTML angled
brackets, but LibreNMS removes these. To put custom links in alerts,
use the bracket/parentheses markdown syntax for links.  For example, if you
usually use this for a Slack link:

`<https://www.example.com|My Link>`

Use this in your alert template:

`[My Link](https://www.example.com)`

**Example:**

| Config | Example |
| ------ | ------- |
| Webhook URL | <https://slack.com/url/somehook> |
| Channel | network-alerts |
| Author Name | LibreNMS Bot |
| Icon | `:scream:` |