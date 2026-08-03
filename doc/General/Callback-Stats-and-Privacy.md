# Submitting Stats

## Stats data and your privacy

This document tells you what LibreNMS does when it sends anonymous
statistics to the LibreNMS project.

All the code that processes the data and submits it is included in the
standard LibreNMS branch that you installed. The code that receives
this data and makes graphs from it is open source and available on
GitHub. You can examine the code, comment on it, and recommend changes
or improvements. Important - by default, installations DO NOT send
data. You must opt in.

We give protection to the privacy of the users. This is why the system
has this design.

The sections below tell you what data is submitted and what we do with
that data.

## What is submitted

- All data is anonymous.
- We take general statistics from the database. These include the
  device count, the device types, the device OS, the port types, the
  port speeds, the port count, and the BGP peer count. Refer to the
  code for the full details.
- We take pairs of sysDescr and sysObjectID from the devices. We clean
  the data to make sure that data such as hostnames is not submitted.
- We record the version numbers of php, mysql, net-snmp and rrdtool
- Your installation makes a random UUID.
- This is all the data.
- We do not keep a record of your IP address, and our web service that
  receives the data does not keep it. We do not need to know who you
  are, thus we do not ask.

## What we do with the data

- We keep the data for a short time - 3 months at this time, but this period can change.
- We use it to make graphs that persons can see.
- We use it to find the problems and features that are the most important.
- We use sysDescr and sysObjectID to make unit tests and to make OS discovery better

## How do I enable stats submission?

If you agree with all of this, you can set the callback system to on.
Do this on the About LibreNMS page in your control panel. The
Statistics section has a switch to enable or disable the feature. If
the feature was on before, and you want to opt out and remove your
data, click the 'Clear remote stats' button. At the next submission,
all the data that you sent to us is removed.


## Questions?

### How often is data submitted?
We submit the data one time each day as part of daily.sh.
If you disable daily.sh, the opt-in has no effect.

### Where can I see the data I submitted?
You cannot see the unprocessed data. We collect all the data and supply a
dynamic site. There you can see the results of all contributed stats [here](https://stats.librenms.org)

### I want my data removed.
Push 'Clear remote stats' on the About LibreNMS page of your control
panel. The next time that the callback script runs, it removes all the data that we have.

### I clicked the 'Clear remote stats' button by accident.
Opt in again before daily.sh runs again. All of
your data stays.

We hope that this answers your questions about what we do and why. If
not, go to our [discord server](https://t.libren.ms/discord) or the
community forum and ask your questions.
