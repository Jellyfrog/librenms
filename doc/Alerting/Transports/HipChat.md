## HipChat

Refer to the HipChat API Documentation for [rooms/message](https://www.hipchat.com/docs/api/method/rooms/message)
for details of the permitted values.

> The link points at the "deprecated" v1 API.  The cause:
> the v2 API is in beta at this time.

**Example:**

| Config | Example |
| ------ | ------- |
| API URL | <https://api.hipchat.com/v1/rooms/message?auth_token=109jawregoaihj> |
| Room ID | 7654321 |
| From Name | LibreNMS |
| Options | color=red |

At this time, these options are supported: `color`.

> Note: The default message format for HipChat messages is HTML.  We
> recommend that you specify the `text` message format to prevent unwanted
> results, for example when HipChat tries to read angled brackets (`<` and
> `>`).