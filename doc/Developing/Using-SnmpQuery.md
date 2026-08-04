## SnmpQuery

To get and manipulate snmp data in LibreNMS, use SnmpQuery.
You can specify Oids in different ways. The recommended way is full textual, such
as IF-MIB::ifIndex. Numeric and short are also supported.

### Actions
When an action occurs, the query runs and returns an
SnmpResponse that holds the returned data. SnmpResponse has many options to
manipulate and index the returned data.

There are 4 primary actions that you can run with SnmpQuery.

 - get - get one or more full oids from the device
 - walk - walk an oid. This is most useful with tables, or with columns from tables
 - next - get the next oid after the specified oid
 - translate - convert an oid between textual and numeric (returns a string)

### Fetch Options

 - numeric - Output all OIDs numerically
 - numericIndex - Output all OIDs numerically
 - abortOnFailure - When walking multiple OIDs, stop if one fails
 - context - Set a context for the snmp query
 - mibDir - Set an additional MIB directory
 - mibs -  Set MIBs to use for this query
 - allowUnordered - No error on out of order indexes (this permits infinite loops)
 - device - specify a different device to query (SnmpQuery always queries the active device)


## SnmpResponse

### value

If the response contains a single value, this returns only the value.
If there is more than one value, you can specify an oid to get from the
response.

##### Examples
 A single value from a single get
 
    SnmpQuery::get('SNMPv2-MIB::sysName.0')->value();
    "server"

The first value to match an oid

    SnmpQuery::walk('IF-MIB::ifTable')->value('IF-MIB::ifIndex');
    "1"

The value for the oid at the given index

    SnmpQuery::walk('IF-MIB::ifTable')->value('IF-MIB::ifDescr.2');
    "enp7s0"


### values

Get all values in an array, with the oid as the key, as snmp returns them.

##### Examples

Walk a single column from ifTable (you can also get all of ifTable, but that is large)
Note: tables use the [] syntax for indexes. All other items use dot syntax.

    SnmpQuery::walk('IF-MIB::ifName')->values();
    [
        "IF-MIB::ifName[1]" => "lo",
        "IF-MIB::ifName[2]" => "enp7s0",
    ]

Get two oids and show both

    SnmpQuery::get(['SNMPv2-MIB::sysObjectID.0', 'SNMPv2-MIB::sysDescr.0'])->values();
    [
        "SNMPv2-MIB::sysObjectID.0" => "NET-SNMP-MIB::netSnmpAgentOIDs.10",
        "SNMPv2-MIB::sysDescr.0" => "Linux 5.15.0-59-generic #62-Ubuntu SMP PREEMPT_DYNAMIC Tue Nov 29 16:25:29 UTC 2022 x86_64",
    ]



### valuesByIndex

Group the values by the full index. 

##### Examples

    SnmpQuery::enumStrings()->walk('IP-MIB::ipAddressTable')->valuesByIndex()
    [
        "ipv4."10.14.32.4"" => [
        "IP-MIB::ipAddressIfIndex" => "3",
        "IP-MIB::ipAddressType" => "unicast",
        "IP-MIB::ipAddressPrefix" => "IP-MIB::ipAddressPrefixOrigin[3][ipv4]["10.14.32.4"][32]",
        ...
    ],
        "ipv4."127.0.0.1"" => [
        "IP-MIB::ipAddressIfIndex" => "1",
        "IP-MIB::ipAddressType" => "unicast",
        "IP-MIB::ipAddressPrefix" => "IP-MIB::ipAddressPrefixOrigin[1][ipv4]["127.0.0.0"][8]",
        ...
    ]

### table

Make a multi dimensional array with an index value as the key of each level.
You can specify a depth at which to group the values, to make the data easier to work
with. The default is 0.

##### Examples

Group by the default depth 0

    SnmpQuery::walk('IP-MIB::ipAddressTable')->table()
    [
        "IP-MIB::ipAddressIfIndex" => [
            "ipv4" => [
                "10.14.32.4" => "3",
                "127.0.0.1" => "1",
            ],
            "ipv6" => [
                "00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:01" => "1",
                "fd:7a:11:5c:a1:e0:00:00:00:00:00:00:9f:e0:6f:72" => "3",
                "fe:80:00:00:00:00:00:00:ae:5a:17:da:13:74:3d:e0" => "3",
                "fe:80:00:00:00:00:00:00:c2:eb:0b:fe:10:21:67:e3" => "2",
            ],
        ],
        "IP-MIB::ipAddressType" => [
    ...

Group by 2 (which matches the index count for this table)

    SnmpQuery::enumStrings()->walk('IP-MIB::ipAddressTable')->table(2)
    [
        "ipv4" => [
            "10.14.32.4" => [
                "IP-MIB::ipAddressIfIndex" => "3",
                "IP-MIB::ipAddressType" => "unicast",
                "IP-MIB::ipAddressPrefix" => "IP-MIB::ipAddressPrefixOrigin[3][ipv4]["10.14.32.4"][32]",
                ...
            ],
            "127.0.0.1" => [
                "IP-MIB::ipAddressIfIndex" => "1",
                "IP-MIB::ipAddressType" => "unicast",
                "IP-MIB::ipAddressPrefix" => "IP-MIB::ipAddressPrefixOrigin[1][ipv4]["127.0.0.0"][8]",
                ...
            ],
    ...

### mapTable

Map an snmp table with a callback. The variables passed to the callback are an
array of row values, followed by each index.

This is the best method to return a collection of data that matches the rows in an SNMP table.

##### Examples

Because this example uses dd() (dump and die), it shows only the first entry.

    SnmpQuery::enumStrings()->walk('IP-MIB::ipAddressTable')->mapTable(function ($data, $ipAddressAddrType, $ipAddressAddr) {
        dd(get_defined_vars());
        // actual closure should return something, like:
        return $$ipAddressAddrType == 'ipv4' ? new Ipv4Address($ipAddressAddr, $data) : new Ipv6Address($ipAddressAddr, $data);
    });
    [
        "data" => [
            "IP-MIB::ipAddressIfIndex" => "3"
            "IP-MIB::ipAddressType" => "unicast"
            "IP-MIB::ipAddressPrefix" => "IP-MIB::ipAddressPrefixOrigin[3][ipv4]["10.14.32.4"][32]"
            "IP-MIB::ipAddressOrigin" => "manual"
            "IP-MIB::ipAddressStatus" => "preferred"
            "IP-MIB::ipAddressCreated" => "3006"
            "IP-MIB::ipAddressLastChanged" => "3006"
            "IP-MIB::ipAddressRowStatus" => "active"
            "IP-MIB::ipAddressStorageType" => "volatile"
        ]
        "ipAddressAddrType" => "ipv4"
        "ipAddressAddr" => ""10.14.32.4""
    ]

### groupByIndex

Get values grouped by the index.  The system does not find the number of index fields.
You must specify it. The default is 1.  This is mostly for numeric oids, when
the system cannot find the index.

##### Examples

    SnmpQuery::numeric()->walk('IF-MIB::ifTable')->groupByIndex(1)
    [
        1 => [
            ".1.3.6.1.2.1.2.2.1.1.1" => "1",
            ".1.3.6.1.2.1.2.2.1.2.1" => "lo",
            ".1.3.6.1.2.1.2.2.1.3.1" => "24",
            ".1.3.6.1.2.1.2.2.1.4.1" => "65536",
            ".1.3.6.1.2.1.2.2.1.5.1" => "10000000",
            ".1.3.6.1.2.1.2.2.1.6.1" => "",
            ".1.3.6.1.2.1.2.2.1.7.1" => "1",
            ...
        ],
        2 => [
        ".1.3.6.1.2.1.2.2.1.1.2" => "2",
        ".1.3.6.1.2.1.2.2.1.2.2" => "enp7s0",
        ".1.3.6.1.2.1.2.2.1.3.2" => "6",
        ".1.3.6.1.2.1.2.2.1.4.2" => "1500",
    ...

### pluck

Get an index-to-key array of the data.  You can specify an oid to get
one column out of an SNMP table.

##### Examples

In this example, the index of the table IF-MIB::ifTable is ifIndex. Thus, when we walk the ifName column
and call pluck, we get a good map of ifIndex to ifName

    SnmpQuery::walk('IF-MIB::ifName')->pluck()
    [
        1 => "lo",
        2 => "enp7s0",
    ]

## Handling errors

Functions to examine the results of the SNMP query.

 - isValid - look for problems such as stopped SNMP walks (such as a network disconnect) and other items.
 - getExitCode - gets the exit code of the snmp process
 - getErrorMessage - returns the stderr output from the process.

