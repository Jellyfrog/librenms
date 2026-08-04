This document gives the information that you need to add
basic detection for a new OS.

### Discovery

With OS discovery, LibreNMS finds the OS to use for a device.
Usually, detection must use sysObjectID or sysDescr. But you can also
snmpget an oid and examine the value.  We do not recommend snmpget, because it makes
all os detections slower, not only the added os.

To start, create the new OS file, with the name
`resources/definitions/os_detection/pulse.yaml`. This is a working example:

```yaml
os: pulse
text: 'Pulse Secure'
type: firewall
icon: pulse
over:
    - { graph: device_bits, text: 'Device Traffic' }
    - { graph: device_processor, text: 'CPU Usage' }
    - { graph: device_mempool, text: 'Memory Usage' }
discovery:
    - sysObjectID:
        - .1.3.6.1.4.1.12532.
```

`over`: This is a list of the graphs shown in the
device header bar (mini graphs at the top right).

`discovery`: Here, we detect this new OS with sysObjectID. This
is the recommended method for detection.  Other options are available:

- `sysObjectID` The recommended operator. Makes sure that the sysObjectID
  starts with one of the strings under this item
- `sysDescr` Use this together with sysObjectID, if necessary. Makes sure
  that the sysDescr contains one of the strings under this item
- `sysObjectID_regex` Do not use this if possible. Makes sure that the
  sysObjectID matches one of the regex statements under this item
- `sysDescr_regex` Do not use this if possible. Makes sure that the sysDescr
  matches one of the regex statements under this item
- `snmpget` Use this only if none of the other methods
  operate. Get an oid and compare it with a value.
```yaml
discovery:
    -
      snmpget:
        - oid: MIB-NAME::someoid
        - op: <["=","!=","==","!==","<=",">=","<",">","starts","ends","contains","regex","not_starts","not_ends","not_contains","not_regex","in_array","not_in_array","exists"]>
        - value: <'string' | boolean>
```
- `_except` You can add this to each of the above, to exclude that
  element. As an example:

```yaml
discovery:
    -
      sysObjectID:
          - .1.3.6.1.4.1.12532.
      sysDescr_except:
          - 'Not some pulse'
```

`group`: You can put some OS' together with group. For
example, ios, nx-os, iosxr are all in a group with the name cisco.

`bad_ifXEntry`: This is a list of models for which LibreNMS must know
that the device does not support ifXEntry, and must ignore it:

```yaml
 bad_ifXEntry:
     - cisco1941
     - cisco886Va
     - cisco2811
```

`mib_dir`: With this, you can specify one more directory in which to
look for MIBs. An array is not accepted. You can specify only one directory.

```yaml
mib_dir: juniper
```

We recommend that you disable only the discovery or poller modules that cause problems for a device.

Usually, Discovery runs first. If it does not discover data, polling does not occur.

`discovery_modules`: This is the list of discovery modules to
enable (1) or disable (0). Refer to `resources/definitions/config_definitions.json` to see
which modules are enabled/disabled by default.

```yaml
discovery_modules:
     cisco-cef: true
     slas: true
```

`poller_modules`: This is a list of poller modules to enable
(1) or disable (0). Refer to `resources/definitions/config_definitions.json` to see which
modules are enabled/disabled by default.

```yaml
poller_modules:
    cisco-ace-serverfarms: false
    cisco-ace-loadbalancer: false
```

##### Discovery Logic

The system converts YAML to an array in PHP.  Examine this YAML:

```yaml
discovery:
  - sysObjectID: foo
  -
    sysDescr: [ snafu, exodar ]
    sysObjectID: bar

```

This is how the discovery array looks in PHP:

```php
[
     [
       "sysObjectID" => "foo",
     ],
     [
       "sysDescr" => [
         "snafu",
         "exodar",
       ],
       "sysObjectID" => "bar",
     ]
]
```

The logic for the discovery is:

1. One of the first level items must match
1. ALL of the second level items must match (sysObjectID, sysDescr)
1. One of the third level items (foo, [snafu,exodar], bar) must match

Thus, for the example:

- `sysObjectID: foo, sysDescr: ANYTHING` matches
- `sysObjectID: bar, sysDescr: ANYTHING` does not match
- `sysObjectID: bar, sysDescr: exodar` matches
- `sysObjectID: bar, sysDescr: snafu` matches

#### OS discovery

OS discovery collects more standard data about the OS.  Specify these in
the discovery yaml `resources/definitions/os_discovery/<os>.yaml`, or in `LibreNMS/OS/<os>.php` if
more complex collection is necessary.

- `version` The version of the OS that runs on the device.
- `hardware` The hardware version of the device. For example: 'WS-C3560X-24T-S'
- `features` Features of the device, for example a list of enabled software features.
- `serial` The main serial number of the device.

##### Yaml based OS discovery

- `sysDescr_regex` apply a regex, or a list of regexes, to the sysDescr to get named groups. This data has the lowest priority
- `<field>` specify an oid, or a list of oids, from which to try to get the data. The system uses the first response that is not empty
- `<field>_regex` parse the value out of the returned oid data. This must use a named group
- `<field>_template` put multiple oid results together to create a final string value.  The system trims the result.
- `<field>_replace` An array of replacements ['search regex', 'replace'], or a regex to remove
- `hardware_mib` MIB used to translate the sysObjectID to get the hardware. hardware_regex can process the result.

```yaml
modules:
    os:
        sysDescr_regex: '/(?<hardware>MSM\S+) .* Serial number (?<serial>\S+) - Firmware version (?<version>\S+)/'
        features: UPS-MIB::upsIdentAttachedDevices.0
        hardware:
            - ENTITY-MIB::entPhysicalName.1
            - ENTITY-MIB::entPhysicalHardwareRev.1
        hardware_template: '{{ ENTITY-MIB::entPhysicalName.1 }} {{ ENTITY-MIB::entPhysicalHardwareRev.1 }}'
        serial: ENTITY-MIB::entPhysicalSerialNum.1
        version: ENTITY-MIB::entPhysicalSoftwareRev.1
        version_regex: '/V(?<version>.*)/'
```

##### PHP based OS discovery

```php
public function discoverOS(\App\Models\Device $device): void
{
    $response = SnmpQuery::next(['NAS-MIB::enclosureModel', 'NAS-MIB::enclosureSerialNum', 'ENTITY-MIB::entPhysicalFirmwareRev']);
    $device->version = $response->value('ENTITY-MIB::entPhysicalFirmwareRev');
    $device->hardware = $response->value('NAS-MIB::enclosureModel');
    $device->serial = $response->value('NAS-MIB::enclosureSerialNum');
}
```

### MIBs

If the device has available MIBs, and you use them in the detection, you can add them. We strongly
recommend that you add mibs to a vendor directory. For example, HP mibs are in `mibs/hp`. Make
 sure that these directories are specified in the yaml detection file. See `mib_dir` above.

### Icon and Logo

We strongly recommend SVG images where possible. These scale, and give a good visual image for users
with HiDPI screens. If you cannot find SVG images, use png.

Create an SVG image of the icon and the logo.  Legacy PNG bitmaps are also supported, but they look bad on HiDPI.

- A vector image must not contain padding.
- The file must not be more than 20 Kb. Make paths simple to decrease large files.
- Use plain SVG without gzip compression.
- The SVG root element must not contain length and width attributes, only viewBox.

##### Icon

- Save the icon SVG to `html/images/os/$os.svg`.
- Icons must look good at 32x32 px.
- Square icons are better than full logos with text.
- Remove small decorations that are almost not visible at 32px width (e.g. ® or ™).

##### Logo

- Save the logo SVG to `html/images/logos/$os.svg`.
- Logos can have each dimension, but they are frequently wide and contain the company name.
- If a logo is not present, the system uses the icon.

##### Hints

Hints for [Inkscape](https://inkscape.org/):

- You can open a PDF or EPS to extract the logo.
- Ungroup elements to isolate the logo.
- Use `Path -> Simplify` to simplify paths of large files.
- Use `File -> Document Properties… -> Resize page to content…` to remove padding.
- Use `File -> Clean up document` to remove unused gradients, patterns, or markers.
- Use `File -> Save As -> Plain SVG` to save the final image.

When you optimize the SVG, you can decrease the file size, in some cases to less than 20 %.
[SVG Optimizer](https://github.com/svg/svgo) does this well. There
is also an [online version](https://jakearchibald.github.io/svgomg/).

#### The final check

Discovery

```bash
lnms device:discover -vv HOSTNAME
```

Polling

```bash
lnms device:poll HOSTNAME
```

At this step, we see all the values received in LibreNMS.

Note: If you made a number of changes to the OS
Discovery files, it is possible that earlier edits are in the cache.
Thus, if you do not get the expected behavior in the final
check above, first try to remove the cache file:

```bash
lnms config:clear
```
