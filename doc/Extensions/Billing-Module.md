# Billing Module

With the billing module, you can create a bill, give it a quota,
and add ports to it. It then monitors the port usage and shows you the
usage in the bill, which includes overage.
Accounting by both total transferred data and 95th percentile is supported.

To enable and use the billing module, do these steps:

!!! setting "system/billing"
    ```bash
    lnms config:set enable_billing true
    ```

=== "Cron"
    Edit `/etc/cron.d/librenms` and add this:
    ```bash
    */5 * * * * librenms /opt/librenms/poll-billing.php >> /dev/null 2>&1
    01  * * * * librenms /opt/librenms/billing-calculate.php >> /dev/null 2>&1
    ```

=== "Dispatcher Service"
    Go to Settings -> Poller -> Settings
    For each poller, make sure that `Billing Enabled` is selected.

## Adding a bill

To create a new bill, select Ports -> Traffic Bills in the LibreNMS menu, and
select `+ Create Bill`.

Enter the applicable details in the form. Make sure that you select a minimum
of one device and port.

## 95th Percentile Calculation

For 95th Percentile billing, the default behavior is to use the
highest of the input or output 95th Percentile calculation.

As an alternative, you can use the combined total of input + output to calculate the 95th percentile.
You can change this for each bill: set 95th Calculation to "Aggregate".

!!! setting "system/billing"
    ```bash
    lnms config:set billing.95th_default_agg true
    ```

This configuration setting is cosmetic. It changes only the default
selected option when you add a new bill.