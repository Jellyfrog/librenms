## JIRA

LibreNMS can create issues on a Jira instance for critical and warning
 alerts, with the Jira REST API or with webhooks. 
With custom fields, you can add necessary fields other than the summary and description
 fields, when your Jira project/issue type configuration makes more fields 
 mandatory. You define custom fields in JSON format. At this time, the system uses http 
 authentication to get access to Jira. The system keeps the Jira username and password as cleartext in the 
 LibreNMS database.

### REST API
The config fields that you must set for the Jira REST API are: Jira Open URL, Jira username, 
Jira password, Project key, and issue type.  

> Note: The REST API can only open new tickets.

### Webhooks
The config fields that you must set for webhooks are: Jira Open URL, Jira Close URL,
 Jira username, Jira password and webhook ID.

> Note: Webhooks give more control of how Jira does alerts. With webhooks, 
> the system can send recovery messages to a different URL than alerts. Also, you can 
> build custom conditional logic with the webhook payload and ID, to automatically close 
> an open ticket when specified conditions occur.


[Jira Issue Types](https://confluence.atlassian.com/adminjiracloud/issue-types-844500742.html)
[Jira Webhooks](https://developer.atlassian.com/cloud/jira/platform/webhooks/)

**Example:**

| Config | Example |
| ------ | ------- |
| Project Key | JIRAPROJECTKEY |
| Issue Type | Myissuetype |
| Open URL | <https://myjira.mysite.com> /  <https://webhook-open-url> |
| Close URL | <https://webhook-close-url>  |
| Jira Username | myjirauser |
| Jira Password | myjirapass |
| Enable webhook | ON/OFF |
| Webhook ID | alert_id |
| Custom Fields | {"components":[{"id":"00001"}], "source": "LibrenNMS"} |