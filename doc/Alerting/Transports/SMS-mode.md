## SMSmode

SMSmode is an SMS provider. You can configure it with the generic API Transport.
You need a token, which is in your personal space.

[SMSmode docs](https://www.smsmode.com/pdf/fiche-api-http.pdf)

**Example:**

| Config | Example |
| ------ | ------- |
| Transport type | Api |
| API Method | POST |
| API URL | http://api.smsmode.com/http/1.6/sendSMS.do |
| Options | accessToken=_PUT_HERE_YOUR_TOKEN_<br/> numero=_PUT_HERE_DESTS_NUMBER_COMMA_SEPARATED_<br />message={{ $msg }} |