## Mail

The E-Mail transport uses the same email-configuration as the rest of LibreNMS.
As a short reminder, these are its configuration directives with the default values:

Emails attach all graphs that the @signedGraphTag directive includes.
If the email format is set to html, the graphs are embedded.
To stop the attachment of images, set email_attach_graphs to false.

!!! setting "alerting/email"
```bash
lnms config:set email_html true
lnms config:set email_attach_graphs false
```

**Example:**

| Config | Example |
| ------ | ------- |
| Email | me@example.com |