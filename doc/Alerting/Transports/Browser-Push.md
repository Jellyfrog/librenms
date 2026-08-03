## Browser Push

Browser push notifications can send a notification to the user's
device, also when the browser is not open. This needs HTTPS, the PHP
GMP extension, [Push
API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)
support, and permission on each device to send alerts.

Configure an alert transport and give notification permission
on the device(s) on which you want to receive alerts.  You can disable
alerts in a browser on the user preferences page.