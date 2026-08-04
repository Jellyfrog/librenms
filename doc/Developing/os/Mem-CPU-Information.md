This document helps you add detection for Memory /
Processor for your new device.

#### Memory

LibreNMS tries to find memory statistics with the standard HOST-RESOURCES-MIB and UCD-SNMP-MIB MIBs.
You can define non-standard MIBs in Yaml for detection.

##### YAML

For correct detection of the memory quantity and usage, two of the four keys below are necessary.  Some OS
give only a usage percentage. This operates, but the system does not show a total RAM quantity.

- total
- used
- free
- percent_used

`resources/definitions/os_discovery/mempools/arubaos.yaml`

```yaml
mempools:
    data:
        -
            total: WLSX-SWITCH-MIB::sysXMemorySize
            used: WLSX-SWITCH-MIB::sysXMemoryUsed
            precision: 1024
```

The code can also read table based OIDs. It supports many of the same features as Health Sensors,
which include {{ }} parsing, skip_values, and precache.

Valid data entry keys:

- `oid` oid to walk to collect processor data
- `total` oid or integer, total memory size in bytes (or precision)
- `used` oid, memory used in bytes (or precision)
- `free` oid, memory free in bytes (or precision)
- `percent_used` oid of the percentage of used memory
- `descr` A visible description of the memory measurement. The default is "Memory"
- `warn_percent` Usage percentage to use for alerts
- `precision` precision for all byte values, usually a power of 2 (1024 for example)
- `class` used to make the rrd filename. The default is system.  If system, buffers, and cached exist, the
system combines them to calculate the available memory.
- `type` used to make the rrd filename. The default is the os name
- `index` used to make the rrd filename. The default is the oid index
- `skip_values` skip values. Refer to [Health Sensors](Health-Information.md) for the specification
- `snmp_flags` more net-snmp flags

##### Custom Processor Discovery and Polling

If you must implement custom discovery or polling, you can implement
the MempoolsDiscovery interface and the MempoolsPolling interface in the OS class.
MempoolsPolling is optional. Standard polling uses the OIDs kept in the database.

OS Class files reside under `LibreNMS\OS`

```php
<?php

namespace LibreNMS\OS;

use LibreNMS\Interfaces\Discovery\MempoolsDiscovery;
use LibreNMS\Interfaces\Polling\MempoolsPolling;

class Example extends \LibreNMS\OS implements MempoolsDiscovery, MempoolsPolling
{
    /**
     * Discover a Collection of Mempool models.
     * Will be keyed by mempool_type and mempool_index
     *
     * @return \Illuminate\Support\Collection \App\Models\Mempool
     */
    public function discoverMempools()
    {
        // TODO: Implement discoverMempools() method.
    }

    /**
     * @param \Illuminate\Support\Collection $mempools \App\Models\Mempool
     * @return \Illuminate\Support\Collection \App\Models\Mempool
     */
    public function pollMempools($mempools)
    {
        // TODO: Implement pollMempools() method.
    }
}
```

#### Processor

Detection for processors uses a yaml file, unless custom
processing of data is necessary.

##### YAML

`resources/definitions/os_discovery/pulse.yaml`

```yaml
mib: PULSESECURE-PSG-MIB
modules:
    processors:
          data:
              -
                  oid: iveCpuUtil
                  num_oid: '.1.3.6.1.4.1.12532.10.{{ $index }}'
                  type: pulse
```

Available yaml data keys:

Key | Default | Description
----- | --- | -----
oid | mandatory | The string based oid to get data. This can be a table or a single value
num_oid | optional | The numerical oid from which polling gets data. Usually, add {{ $index }} at the end. The discovery procedure calculates it if you do not give it.
value | optional | Oid from which to get data, primarily for tables
precision | 1 | The multiplier for the data. If this is negative, the system multiplies the data, then subtracts it from 100.
descr | Processor | Description of this processor. This can be an oid or a plain string.  Useful values: {{ $index }} and {{$count}}
type | <os name> | Name of this sensor. The system uses this with the index to make a unique id for this sensor.
index | {{ $index }} | The index of this sensor. The default is the index of the oid.
skip_values | optional | Do not detect this sensor if the value matches

Accessing values within yaml:

| | |
| --- | --- |
| {{ $index }} | The index after the given oid |
| {{ $count }} | The count of entries (starting with 1) |
| {{ $`oid` }} | Any oid in the table or pre-fetched |

##### Custom Processor Discovery and Polling

If you must implement custom discovery or polling, you can implement
the ProcessorDiscovery interface and the ProcessorPolling interface in the OS class.

OS Class files reside under `LibreNMS\OS`

```php
<?php
namespace LibreNMS\OS;

use LibreNMS\Device\Processor;
use LibreNMS\Interfaces\Discovery\ProcessorDiscovery;
use LibreNMS\Interfaces\Polling\ProcessorPolling;
use LibreNMS\OS;

class ExampleOS extends OS implements ProcessorDiscovery, ProcessorPolling
{
    /**
     * Discover processors.
     * Returns an array of LibreNMS\Device\Processor objects that have been discovered
     *
     * @return array Processors
     */
    public function discoverProcessors()
    {
        // discovery code here
    }

    /**
     * Poll processor data.  This can be implemented if custom polling is needed.
     *
     * @param array $processors Array of processor entries from the database that need to be polled
     * @return array of polled data
     */
    public function pollProcessors(array $processors)
    {
        // polling code here
    }
}
```
