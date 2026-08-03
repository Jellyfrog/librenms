# Rules

The easiest method to test if an alert rule matches a device:
go to the device, click edit (the cog), and select Capture. On
this new screen, select Alerts and click run.

The output goes through all alerts applicable to this device. It
shows you the Rule name, the rule, the MySQL query and if the rule matches.

See [Device Troubleshooting](../Support/Device-Troubleshooting.md)

---

## Transports

To test your transports, you can force an active alert to run,
independently of the interval or delay values.

`./scripts/test-alert.php`. This script accepts -r for the rule id, -h
for the device id or hostname and -d for debug.

---

## Templates

You can test your new template before you attach it to a
rule. To do this, run `./scripts/test-template.php`. When you run the
script without parameters, it shows the help information.

As an example, to test template ID 10 on localhost
with rule ID 2, run:

`./scripts/test-template.php -t 10 -d -h localhost -r 2`

If the rule is in alert for localhost at that time, you get the
full template, as you see it in an email. If it is not, you
see only the template, without fault information.
