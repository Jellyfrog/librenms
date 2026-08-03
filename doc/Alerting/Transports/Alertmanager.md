## Alertmanager

Alertmanager is alert handling software. It was initially made to
process alerts that Prometheus sends.

It has built-in functions that remove duplicates, group alerts, and
send alerts on routes, from configurable criteria.

LibreNMS groups alerts by alert rule. This can make an array
of alerts with similar content for an array of hosts. Alertmanager
can group them by alert meta. Then, when a problem occurs, you
ideally get one single notice.

You can configure as many label values as necessary in the
Alertmanager Options section. Enter each label and its value
as a new line.

Labels can be a fixed string, or a dynamic variable from the alert and its faults.
To set dynamic variables, the label's value must be the name of
the variable that you want to get (to see all the variables, go to
Alerts->Notifications and click the Details icon of your alert
when it is pending). 

Labels with the prefix "dyn_" are not included in the transport message
if no matching value exists in the alert data. Labels without this
prefix are always included. They use their fixed string value when there is no match.

Labels with the prefix "stc_" are static. 
The system never does value replacement on them.

Multiple Alertmanager URLs (comma separated) are supported. The system 
tries all of them. Alertmanager clustering must remove the duplicate alerts.

Basic HTTP authentication with a username and a password is supported.
If you keep those values empty, the system uses no authentication.

[Alertmanager Docs](https://prometheus.io/docs/alerting/alertmanager/)

**Example:**

| Config | Example |
| ------ | ------- |
| Alertmanager URL(s)   | http://alertmanager1.example.com,http://alertmanager2.example.com |
| Alertmanager Username | myUsername |
| Alertmanager Password | myPassword |
| Alertmanager Options: | source=librenms <br/> customlabel=value <br/> extra_dynamic_value=variable_name |
