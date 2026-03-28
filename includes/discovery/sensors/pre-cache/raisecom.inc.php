<?php

echo 'raisecomOpticalTransceiverDDMTable ';
$pre_cache['raisecomOpticalTransceiverDDMTable'] = SnmpQuery::hideMib()->walk('RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverDDMTable')->table(2);
