<?php

// PHPStan scan file: constants and classes of the librenms-snmp extension
// (https://github.com/jellyfrog/pie-snmp) that PHP's own ext/snmp does not have.
// Never loaded at runtime. Methods added to SNMP and SNMPException are in librenms-snmp.stub.

namespace {
    /** native PHP values instead of the strings Net-SNMP prints */
    const SNMP_VALUE_NATIVE = 4;
    /** with SNMP_VALUE_NATIVE: an enum INTEGER as its MIB label */
    const SNMP_VALUE_ENUM_LABELS = 8;
}

namespace Snmp {
    /** an SNMP exception varbind, returned as a value with SNMP_VALUE_NATIVE */
    enum Varbind
    {
        case NoSuchObject;
        case NoSuchInstance;
        case EndOfMibView;
    }
}
