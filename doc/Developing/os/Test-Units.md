# Tests

Tests make sure that LibreNMS operates correctly, now and in the future.  A new
OS must supply as much test data as necessary. More test data for
an existing OS is welcome.

Saved snmp data is in `tests/snmpsim/*.snmprec`, and saved
database data is in `tests/data/*.json`. Examine this
for sensitive data **before** you submit.  When you replace data,
make sure that the changes are consistent.

> We use [snmpsim](http://snmpsim.sourceforge.net/) for unit
> tests. For OS discovery, we can mock snmpsim. But for other tests,
> it must be installed and serviceable.  We run snmpsim during
> our integration tests, but not by default when we run
> `lnms dev:check`.  You can install snmpsim with the
> command `pip3 install snmpsim`.

## Capturing test data

???+ warning "If test data already exists"

> If test data already exists, but for a different
> device/configuration with the same OS,
> use the `--variant (-v)` option to
> specify a different variant of the OS.
> The system tests this fully separate from other variants.
> If there is only one variant, do not specify one.

### 1. Collect SNMP data

With `lnms dev:collect-snmprec`, you can easily
collect data for tests.  Run `dev:collect-snmprec` with
`<device> --variant ''` to capture all data used to discover and
poll a device already added to LibreNMS.  Make sure that you run the
command again if you add more support. Refer to the command-line help for
more options.

### 2. Save test data

After you collect the snmp data, run `./scripts/save-test-data.php`
with the `--os (-o) -v ''` option. This writes the post discovery and post poll
database entries to json files. This step needs snmpsim. If you
have problems, the maintainers can help you make it from the
snmprec that you created in the step before.

Usually, you must collect data only one time.
When you have the necessary data in the snmprec file, you can use
save-test-data.php to update the database dump (json) after that.

## Running tests

**Note:** To run tests, make sure that you ran
`./scripts/composer_wrapper.php install` from your LibreNMS root
directory. This reads composer.json and installs the necessary dependencies.

After you save your test data, run
`lnms dev:check` to make sure that the tests pass.

To run the full set of tests, enable the tests that need the database and snmpsim:
`lnms dev:check unit --db --snmpsim`

### Specific OS

`lnms dev:check unit -o osname`

### Test an OS, but only discovery and polling modules (exluding OS detection)
`lnms dev:check unit --os osname --os-modules-only`


### Specific Module

`lnms dev:check unit -m modulename`

### Test all modules for all os and stop on failure
`lnms dev:check unit --db -snmpsim --os-modules-only -f`

## Using snmpsim for testing

You can run snmpsim to access test data by running

```bash
lnms dev:simulate
```

You can then run snmp queries against it. Use the os (and variant) as
the community, and 127.1.6.1:1161 as the host.

```bash
snmpget -v 2c -c ios_c3560e 127.1.6.1:1161 sysDescr.0
```

## Simulate specific device from test data

Add/update a device with the name "snmpsim" in your installation, and set it to use a specified snmprec file

```bash
lnms dev:simulate ios_2960x
```

You can then run `lnms device:discover snmpsim -vv` and `lnms device:poll snmpsim -vv`
to discover and poll the simulated device.

## Snmprec format

Snmprec files are simple files that keep the snmp data. The data
format is simple, with three columns: numeric oid, type code, and
data. This is an example.

```snmp
1.3.6.1.2.1.1.1.0|4|Pulse Secure,LLC,MAG-2600,8.0R14 (build 41869)
1.3.6.1.2.1.1.2.0|6|1.3.6.1.4.1.12532.254.1.1
```

During tests, LibreNMS uses the information in the snmprec file for snmp
calls.  This one gives sysDescr (`.1.3.6.1.2.1.1.1.0`, 4 = Octet
String) and sysObjectID (`.1.3.6.1.2.1.1.2.0`, 6 = Object Identifier).
This is the minimum that new snmprec files must give.

To look up the numeric OID and the type of a string OID with snmptranslate:

```bash
snmptranslate -On -Td SNMPv2-MIB::sysDescr.0
```

List of SNMP data types:

| Type              | Value         |
| ----------------- | ------------- |
| OCTET STRING      | 4             |
| HEX STRING        | 4x            |
| Integer32         | 2             |
| NULL              | 5             |
| OBJECT IDENTIFIER | 6             |
| IpAddress         | 64            |
| Counter32         | 65            |
| Gauge32           | 66            |
| TimeTicks         | 67            |
| Opaque            | 68            |
| Counter64         | 70            |

Use hex encoded strings (4x) for strings that contain line returns.

## New discovery/poller modules

New discovery or poller modules must define database capture parameters in `/tests/module_tables.yaml`.

## Example workflow

If the base os (<os>.snmprec) already contains test data for the
module that you test, or if that data conflicts with your new data, you
must use a variant to keep your test data (-v <variant>).

### Add initial detection

1. Add the device to LibreNMS. It is generic and device_id = 42
1. Run `lnms dev:collect-snmprec 42 --variant ''`. This creates the initial snmprec
1. [Add initial detection](Initial-Detection.md) for `example-os`
1. Run discovery to make sure that detection is correct: `lnms device:discover -vv 42`
1. Add more os items, such as version, hardware, features, or serial.
1. If more snmp data is necessary, run
   `lnms dev:collect-snmprec 42 --variant ''`
1. Run `./scripts/save-test-data.php -o example-os` to update the
   dumped database data.
1. Examine the data. If you changed the snmprec or the code (do not change the json
   manually), run `./scripts/save-test-data.php -o example-os -m os -v ''`
1. Run `lnms dev:check unit --db --snmpsim`
1. If the tests pass, submit a pull request

### Additional module support or test data

1. Add code to support the module, or the support already exists.
1. `lnms dev:collect-snmprec 42 --variant '' -m <module>`. This adds
   more data to the snmprec file
1. Examine the data. If you changed the snmprec (do not change the json
   manually), run `./scripts/save-test-data.php -o example-os -v '' -m <module>`
1. Run `lnms dev:check unit --db --snmpsim`
1. If the tests pass, submit a pull request

## JSON Application Test Writing Using ./scripts/json-app-tool.php

1. First, you need a good example of the JSON output that the applicable SNMP
   extend makes.
1. Read the help with `./scripts/json-app-tool.php -h`.
1. Make the SNMPrec data with `./scripts/json-app-tool.php -a
   appName -s > ./tests/snmpsim/linux_appName-v1.snmprec`. If the
   SNMP extend name OID is different from the application name, you
   must pass  the -S flag to replace it.
1. Make the test JSON data with `./scripts/json-app-tool.php -a
   appName -t > ./tests/data/linux_appName-v1.json`.
1. Update the './tests/data/linux_appName-v1.json' file. Make
   sure that all the expected metrics are present. This applies when
   all data under .data in the JSON is collapsed and used.

If, during test runs, the system does not find the app, and the app
has a different app name and SNMP extend name OID, make sure that -S
is set correctly, and that 'includes/discovery/applications.inc.php' is
updated.
