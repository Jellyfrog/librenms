## aspSMS

aspSMS is an SMS provider. You can configure it with the generic API Transport.
You need a token, which is in your personal space.

[aspSMS docs](https://www.aspsms.com/en/documentation/)

**Example:**

| Config | Example |
| ------ | ------- |
| Transport type | Api |
| API Method | POST |
| API URL | https://soap.aspsms.com/aspsmsx.asmx/SimpleTextSMS |
| Options | UserKey=USERKEY<br />Password=APIPASSWORD<br />Recipient=RECIPIENT<br/> Originator=ORIGINATOR<br />MessageText={{ $msg }} |