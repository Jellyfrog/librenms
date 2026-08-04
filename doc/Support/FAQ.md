## Getting started

### <a name="faq1"> How do I install LibreNMS?</a>

This is fully documented in the doc folder of the installation files.

Refer to this [doc](../Installation/Install-LibreNMS.md)

### <a name="faq2"> How do I add a device?</a>

There are two methods to add a new device into LibreNMS.

1: On the command line, through ssh, you can add a new device. Change
   to the directory of your LibreNMS installation and type:

```bash
lnms device:add [hostname or ip]
```

To see all options, run: `lnms device:add -h`

> If the community contains special characters, such
> as `$`, you must put it in `'`. That is, `'Pa$$w0rd'`.

2: In the web interface, go to Devices and then Add Device. Enter
   the necessary details for the device that you want to add. Then
   click 'Add Host'.

### <a name="faq3"> How do I get help?</a>

[Getting Help](index.md)

### <a name="faq4"> What are the supported OSes for installing LibreNMS on?</a>

Supported is quite a strong word :) The 'officially' supported distros are:

- Ubuntu / Debian
- Red Hat / CentOS
- Gentoo

But we always try to help where possible. If your
distro is not one of the above, try it.
If you need help, go to the [discord
server](https://t.libren.ms/discord).

### <a name="faq5"> Do you have a demo available?</a>

Yes. You can get access to the demo [here](https://demo.librenms.org)


## Support

### <a name='how-does-librenms-use-mibs'>How does LibreNMS use MIBs?</a>

LibreNMS does not parse MIBs to discover sensors for devices.
LibreNMS uses static discovery definitions written in YAML or PHP.
Thus, an update of a MIB alone does not make OS support better. The
definitions must be updated.  LibreNMS uses MIBs only to make OIDs
easier to read.

### <a name="faq6"> Why do I get blank pages sometimes in the WebUI?</a>

To enable debug information, set `APP_DEBUG=true` in your
.env. (Do not keep this enabled. It can release private data)

If the page that you try to load contains much data,
it is possible that you must increase the php memory limit in
[config.php](Configuration.md#core).

### <a name="faq10"> Why do I not see any graphs?</a>

The easiest method to find problems is to run `./validate.php` as
librenms from your installation directory. This gives you information
about why the system does not work.

One other possible cause is a restricted snmpd.conf file or snmp view,
which limits the data sent back. If you use net-snmp, we recommend
the [included
snmpd.conf](https://raw.githubusercontent.com/librenms/librenms/master/snmpd.conf.example)
file.

### <a name="faq7"> How do I debug pages not loading correctly?</a>

There is a debug system. With it, you can see the output from
php errors, warnings and notices, together with the MySQL queries that
ran for that page.

To enable debug information, set `APP_DEBUG=true` in your
.env. (Do not keep this enabled. It can release private data) To see
more information, run `./scripts/composer_wrapper.php install`
to install more debug tools. This adds a debug bar at the
bottom of each page. The bar shows you detailed debug information.

### <a name="faq11"> How do I debug the discovery process?</a>

Refer to the [Discovery Support](Discovery%20Support.md) document
for more details.

### <a name="faq12"> How do I debug the poller process?</a>

Refer to the [Poller Support](Poller%20Support.md) document
for more details.

### <a name="faq14"> Why do I get a lot apache or rrdtool zombies in my process list?</a>

If this is related to your web service for LibreNMS, the cause is
a problem in php that the developers do not
repair. We made a workaround. Thus, you usually do not
see this. If you see it, report it in [issue
443](https://github.com/librenms/librenms/issues/443).

### <a name="faq15"> Why do I see traffic spikes in my graphs?</a>

This occurs when a counter goes back to zero, or when the device sends
incorrect data that looks like a counter reset. There is
support for a maximum value for rrd files for ports.

Before this, all rrd files had a maximum value of 100G. Now you can
enable support to limit this to the true port speed.

rrdtool tune changes the maximum value when the system finds that
the interface speed changed (the minimum value is set for each speed
of 10M or more), or when you run the included script (lnms port:tune) -
refer to the [RRDTune doc](../Extensions/RRDTune.md)

 SNMP ifInOctets and ifOutOctets are counters. They start
 at 0 (at device boot) and count up from there. LibreNMS records the
 value each 5 minutes. It uses the difference between the last
 value and the current value to calculate the rate. (Also, this value
 goes back to 0 when it gets to the maximum value)

When no value is recorded for some time, RRD (our time series
storage) does not record a 0. It records the last value. If it did
not, the problems become worse. Then we get the current
ifIn/OutOctets value and record it. Now it looks as if all
the traffic since the last good value occurred in the last
5 minute interval.

Thus, when you see spikes like this, we did not receive data from the device for a number of polling intervals. There are many possible causes: bad snmp implementations, intermittent network connectivity, a broken poller, and more.

### <a name="faq17"> Why do I see gaps in my graphs?</a>

The usual cause is a poller that cannot complete
its run in 300 seconds. To find the devices that cause this,
go to /poll-log/ in the Web interface.

When you find the device(s) that take the longest time,
examine the Polling module graph under Graphs -> Poller -> Poller
Modules Performance. Find the modules that take the
longest time, and disable the modules that you do not use.

If you poll a large number of devices / ports, we recommend that you
run a local recursive dns server, such as pdns-recursor.

We also strongly recommend RRDCached for larger installations. But it
helps for all sizes.

### <a name="faq16"> How do I change the IP / hostname of a device?</a>

There is a host rename tool, renamehost.php, in your librenms
root directory. When you rename a device, you also change the IP /
hostname address that the system monitors.

Usage:

```bash
./renamehost.php <old hostname> <new hostname>
```

You can also rename a device in the Web UI. Go to the device,
then click the settings Icon -> Edit.

### <a name="faq19"> My device does not complete polling in 300 seconds</a>

You can try these steps:

- Disable unnecessary polling modules under edit device.
- Set a max repeater value in the snmp settings for a device. The
  correct value is not easy to find. Run an snmpbulkwalk with
  -Cr10 through -Cr50 to see which value is best. 50 is usually a good
  selection, if the device can operate with it.

### <a name="faq18"> Things do not operate correctly?</a>

Run `./validate.php` as librenms from your installation.

Run `./validate.php` again after you correct the reported problems.

If you have an unusual problem, we recommend that you go to our [discord
server](https://t.libren.ms/discord) to speak about it.

### <a name="faq21"> What do the values mean in my graphs?</a>

The values that you see are metric values. Because of a post on
[Reddit](https://www.reddit.com/r/networking/comments/4xzpfj/rrd_graph_interface_error_label_what_is_the_m/),
here are those values:

```
10^-18  a - atto
10^-15  f - femto
10^-12  p - pico
10^-9   n - nano
10^-6   u - micro
10^-3   m - milli
0    (no unit)
10^3    k - kilo
10^6    M - mega
10^9    G - giga
10^12   T - tera
10^15   P - peta
```

### <a name="faq22"> Why does a device show as a warning?</a>

This shows that the device did a reboot in the last 24
hours (by default). To adjust this threshold,
set `$config['uptime_warning'] = '86400';` in
`config.php`. The value must be in seconds.

### <a name="faq23"> Why do I not see all interfaces in the Overall traffic graph for a device?</a>

By default, many interface types and interface descriptions are
not included in this graph. The default exclusions are:

```php
$config['device_traffic_iftype'][] = '/loopback/';
$config['device_traffic_iftype'][] = '/tunnel/';
$config['device_traffic_iftype'][] = '/virtual/';
$config['device_traffic_iftype'][] = '/mpls/';
$config['device_traffic_iftype'][] = '/ieee8023adLag/';
$config['device_traffic_iftype'][] = '/l2vlan/';
$config['device_traffic_iftype'][] = '/ppp/';

$config['device_traffic_descr'][] = '/loopback/';
$config['device_traffic_descr'][] = '/vlan/';
$config['device_traffic_descr'][] = '/tunnel/';
$config['device_traffic_descr'][] = '/bond/';
$config['device_traffic_descr'][] = '/null/';
$config['device_traffic_descr'][] = '/dummy/';
```

If you want to include l2vlan interfaces again, for example,
first `unset` the config array. Then set your options:

```php
unset($config['device_traffic_iftype']);
$config['device_traffic_iftype'][] = '/loopback/';
$config['device_traffic_iftype'][] = '/tunnel/';
$config['device_traffic_iftype'][] = '/virtual/';
$config['device_traffic_iftype'][] = '/mpls/';
$config['device_traffic_iftype'][] = '/ieee8023adLag/';
$config['device_traffic_iftype'][] = '/ppp/';
```

### <a name="faq24"> How do I migrate my LibreNMS install to another server?</a>

If you move from one CPU architecture to a different one, you must
dump the rrd files and create them again. In this
condition, you can use [Dan Brown's migration
scripts](https://web.archive.org/web/20180815212723/https://vlan50.com/2015/04/17/migrating-from-observium-to-librenms/).

If you only move to a different server with the same CPU
architecture, these steps are usually sufficient:

- Install LibreNMS as given in our usual documentation. It is not
  necessary to run the web installer or to build the sql schema.
- Stop cron. To do this, comment out all lines in `/etc/cron.d/librenms`
- Dump the MySQL database `librenms` from your old server (`mysqldump
  librenms -u root -p > librenms.sql`)...
- and import it into your new server (`mysql -u root -p librenms < librenms.sql`).
- Copy the `rrd/` folder to the new server.
- Copy the `.env` and `config.php` files to the new server.
- Look for changed files (eg specific os, ...) with `git status` and
  migrate them.
- Make sure that the ownership of the copied files and folders is
  correct (use your user if necessary) - `chown -R librenms:librenms /opt/librenms`
- Delete the old pollers in the GUI (gear icon --> Pollers --> Pollers)
- Validate your installation (/opt/librenms/validate.php)
- Enable cron again. To do this, uncomment all lines in `/etc/cron.d/librenms`

### <a name="faq25"> Why is my EdgeRouter device not detected?</a>

If `service snmp description` is set in your config, this
is the cause. Remove this setting. On Ubnt devices,
this value replaces the sysDescr value that the device returns. This
breaks our detection.

If you do not have that setting, the cause can be an update of
EdgeOS or a new device type. [Create an
issue](https://github.com/librenms/librenms/issues/new).

### <a name="faq26"> Why are some of my disks not showing?</a>

If you monitor a linux server, net-snmp does not always
show all disks through hrStorage (HOST-RESOURCES-MIB). We have
more support that gets disks through dskTable
(UCD-SNMP-MIB). To show these disks, you must add more
configuration to your snmpd.conf file. For example, to show `/dev/sda1`,
which is possibly mounted as `/storage`, you can specify:

`disk /dev/sda1`

Or

`disk /storage`

Restart snmpd. After a new discovery, LibreNMS shows the added disk.

#### <a name="faq27"> Why are my disks reporting an incorrect size?</a>

There is a known problem with net-snmp. It reports an
incorrect disk size and disk usage when the size of the disk (or raid)
is more than 16TB. There is a workaround, but it is not
active on Centos 6.8 by default. The cause: this workaround
does not obey the official SNMP specifications. Thus, it can cause
unwanted behavior in other SNMP tools. To make the workaround active,
add this to /etc/snmp/snmpd.conf :

`realStorageUnits 0`

### <a name="faq28"> What does mean \"ignore alert tag\" on device, component, service and port?</a>

Tag a device, component, service or port to ignore alerts. Alert checks continue to run.
But alert rules can read the ignore tag. For example, on a device, if the `devices.ignore = 0`
or `macros.device = 1` condition is set, and the ignore alert tag is on,
the alert rule does not match. The system ignores the alert rule.

### <a name="network-config-permanent-change"> How do I clean up alerts from my switches and routers about ports being down or changing speed</a>

Some properties used for alerting (which end in `_prev`) get updates only when
the system finds a change, not each time the poller runs. Thus, if you
make a permanent change to your network, such as when you remove a device, do a
large firmware upgrade, or downgrade a WAN connection, some alerts can
stay on and cannot clear.

If a port stays down permanently, the best procedure is to configure it as
administratively down on the device. This prevents malicious access. Then you can
run alerts only on ports with `ifAdminStatus = up`. If you do not do this, you must
clear the port state history of the device.

On the device that causes the alerts, use the cog button to go to the edit device
page. At the top of the _device settings_ pane is a button with the label `Reset Port
State`. This removes the old state for all ports on that device.
Then the active alerts can clear.



### <a name="faq29"> Why can Normal and Global View users not see Oxidized?</a>

Configs frequently contain sensitive data. Because of this, only global
admins can see configs.

### <a name="faq30"> What is the Demo User for?</a>

Demo users have full access, but they cannot add or edit users, cannot
delete devices, and cannot change passwords.

### <a name="faq31"> Why does modifying 'Default Alert Template' fail?</a>

It is possible that the entry for this template is missing in the
database. Run this from the LibreNMS directory:

```bash
php artisan db:seed --class=DefaultAlertTemplateSeeder
```

### <a name="faq32"> Why does an alert unmute itself?</a>

If an alert unmutes itself, the probable cause is that the alert
cleared and then started again. Examine the eventlog. It
shows this.

### <a name="faq33"> How do I change the Device Type?</a>

To change the Device Type, go to the applicable device.
Then click the Gear Icon -> Edit. If you want to
define custom types, we recommend [Device
Groups](../Extensions/Device-Groups.md). The menu shows them
almost the same as device types.

### <a name="faq34"> Editing large device groups gives error messages</a>

If the device group contains a large number of devices, and you edit it in the UI, errors can occur on the form, also when all the data looks correct. The cause is PHP's `max_input_vars` variable. To make sure that this is the cause, examine the PHP error logs.

With the basic installation on Ubuntu 22.04 LTS with Nginx and PHP 8.1 FPM, you can change this value in the file `/etc/php/8.1/fpm/php.ini`. Set the value of `max_input_vars` to a minimum of the size of the large group. In larger installations, a value such as `10000` is usually sufficient.

### <a name="faq-where-do-i-update-my-database-credentials">Where do I update my database credentials?</a>

If you changed your database credentials, you must
update LibreNMS with the new details.
Edit `.env`

[.env](../Support/Environment-Variables.md#database):

```dotenv
DB_HOST=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
DB_PORT=
```

### <a name='my-reverse-proxy-is-not-working'>My reverse proxy is not working</a>

Make sure that your proxy sends the correct variables.
As a minimum: X-Forwarded-For and X-Forwarded-Proto (X-Forwarded-Port if necessary)

You must also [set the proxy or proxies as trusted](../Support/Environment-Variables.md#trusted-reverse-proxies)

If you use a subdirectory on the reverse proxy, and not on the web server,
it is possible that you must set [APP_URL](../Support/Environment-Variables.md#base-url) and `$config['base_url']`.

### <a name='my-alerts-aren't-being-delivered-on-time'>My alerts do not arrive on time</a>

If you run MySQL/MariaDB on a separate machine or container,
make sure that the timezone is set correctly on the LibreNMS **and**
the MySQL/MariaDB instance. The system sends alerts at the
MySQL/MariaDB time. Thus, a difference between the two can cause late
alerts, if LibreNMS is on a timezone later than
MySQL/MariaDB.

### <a name='my-alert-templates-stopped-working'>My alert templates stopped working</a>

Read the documentation about the
new [template syntax](../Alerting/Templates.md). In version 1.42,
the syntax changed. You must convert your templates to
this new syntax (which includes the titles).

### <a name='how-do-i-use-trend-prediction-in-graphs'>How do I use trend prediction in graphs</a>

[Ver. 1.55](https://community.librenms.org/t/v1-55-release-changelog-august-2019/9428) added a new feature. With it, you can see a simple linear prediction in port graphs.

> It does not operate on non-port graphs or consolidated graphs at the time this FAQ entry was written.

To see a prediction:

- Click a `port` graph of a network device
- Select a `From` date (not earlier than the date when the device was added to LNMS). Then select a future date in the `To` field.
- Click update

You now see a linear prediction line on the graph.
### <a name='move-db-to-another-server'>How do I move only the DB to another server?</a>

There is already a reference for how to move your full LNMS installation to a different server. But the steps below help you divide an "All-in-one" installation into one LibreNMS installation with a separate database installation.
*Note: This section applies when you have a MySQL/MariaDB instance

- Stop the apache and mysql services in your LibreNMS installation.
- Edit out all the cron entries in `/etc/cron.d/librenms`.
- Dump your `librenms` database on your current installation with `mysqldump librenms -u root -p > librenms.sql`.
- Stop and disable the MySQL server on your current installation.
- On your new server, make sure that you create a new database with the standard installation command. It is not necessary to add a user for localhost.
- Copy the dump to your new database server and import it with `mysql -u root -p librenms < librenms.sql`.
- Go into mysql and add permissions with these two commands:
```sql
GRANT ALL PRIVILEGES ON librenms.* TO 'librenms'@'IP_OF_YOUR_LNMS_SERVER' IDENTIFIED BY 'PASSWORD' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON librenms.* TO 'librenms'@'FQDN_OF_YOUR_LNMS_SERVER' IDENTIFIED BY 'PASSWORD' WITH GRANT OPTION;
FLUSH PRIVILEGES;
exit;
```
- Enable and restart the MySQL server.
- Edit your `config.php` file to point the installation to the new database server location.
- **Very important**: On your LibreNMS server, in your installation directory, is a `.env` file. In it, you must edit the `DBHOST` parameter to point to your new server location.
- After all this is done, enable all the cron entries again and start apache.
### <a name='optional-requirements-for-snmpv3-sha2-auth'>What are the "optional requirements message" when I add SNMPv3 devices?</a>
When you add a device in the WebUI, it is possible that you see a small message: "Optional requirements are not met so some options are disabled". This is not a problem. It only means that your system does not contain **openssl >= 1.1** and **net-snmp >= 5.8**. These are the minimum versions necessary to use SHA-224|256|384|512 as auth algorithms.
For the crypto algorithms AES-192 and AES-256, you need **net-snmp** compiled with `--enable-blumenthal-aes`.


## Developing

### <a name="faq8"> How do I add support for a new OS?</a>

Refer to [Supporting a new OS](../Developing/Support-New-OS.md) if you add all
the support yourself, that is, you write all the necessary code. If you can only
supply information, and you want the help of others to write the code,
do the steps below.

### <a name="faq20"> What information do you need to add a new OS?</a>

[Open a feature request in the community forum](https://community.librenms.org/c/feature-requests) and give
the output of Discovery, Poller, and Snmpwalk as separate "pastebin"
links that do not expire. We recommend <https://paste.rs/> or <https://paste.sh/>

We recommend that you use the command line to get the information,
specially if snmpwalk gives a large quantity of data. Replace the
applicable information in these commands, such as HOSTNAME and
COMMUNITY. Use `snmpwalk`, not `snmpbulkwalk`, for v1 devices.

> These commands automatically upload the data to the <https://paste.rs/> servers.
> You can use a different service!

```bash
lnms device:discover -vv HOSTNAME | curl --data-binary @- https://paste.rs/
lnms device:poll -vv HOSTNAME | curl --data-binary @- https://paste.rs/
snmpbulkwalk -OUneb -v2c -c COMMUNITY HOSTNAME . | curl --data-binary @- https://paste.rs/
```

You can use the links that these commands give in the community post.

If possible, also tell us what the OS name must be, if it does not exist,
and give applicable links (MIBs from the vendor, logo, and so on and so on)

### <a name="faq9"> What can I do to help?</a>

Thank you for the question. The answer is not always clear, and each
person can contribute something different. These are some ways to help
make LibreNMS better.

- Code. This is a big item. We want this community to grow through
  software that develops and changes to do what the users need. The
  largest area where people can help is
  code contributions. This does not always mean code for
  the discovery of a new device:
  - Web UI. There is a new look and feel, but it is not
      complete. Make suggestions,
      find and repair bugs, update the design / layout.
  - Poller / Discovery code. Make it better (we think that much can be
    done to make it faster), add new device support, and update old
    device support.
  - The LibreNMS main website. This is on GitHub, like the main
    repo, and we accept contributions here as well :)
- Hardware. We do not need the hardware itself. But when we add device
  support, access to the equipment through
  SNMP makes it much easier.
  - If you have MIBs, they are useful as well :)
  - If you know the vendor, and can get permission to use logos, that is also good.
- Bugs. Did you find one? We want to know about it. Most bugs are
  repaired after a person sees and reports them. We want to say
  that we are perfect developers who repair all bugs before you see
  them, but that is not true.
- Feature requests. You cannot code, or do not want to code? That is
  not a problem. Put a feature request into our [community
  forum](https://community.librenms.org) with sufficient detail, and
  a person examines it. Frequently, a feature interests a person,
  or they need the same feature, or they have
  time. Be patient. Each person who contributes does so in their
  own time.
- Documentation. Documentation can always be made better, and each small
  contribution helps. Not all features have documentation, or good
  documentation, and there are spelling errors and so on. It is easy to submit
  updates [through the GitHub
  website](https://help.github.com/articles/editing-files-in-another-user-s-repository/).
  No git experience is necessary.
- Be nice. This is the foundation of this project. We expect each
  person to be nice. People do not always agree. But do
  it in a way that shows respect.
- Ask questions. Sometimes a question starts deeper
  conversations that lead us to a good result. Thus, never
  be afraid to ask a question.

### <a name="faq13"> How can I test another users branch?</a>

Each person can develop LibreNMS. This means that a person is possibly
working on a new feature, or on support for a device that you want. It
helps when others test these new features. With Git, this is
easy.

```bash
cd /opt/librenms
```

First, make sure that your current branch is in a good state:

```bash
git status
```

If you see `nothing to commit, working directory clean`, you can continue :)

For example, you want to test a user's (f0o) new development branch
(issue-1337). Then you can do this:

```bash
git remote add f0o https://github.com/f0o/librenms.git
git remote update f0o
git checkout issue-1337
```

When your tests are complete, you can easily change back to the master branch:

```bash
git checkout master
```

If you want to pull new updates from f0o's branch,
stay in the branch and do this:

```bash
git pull f0o issue-1337
```
