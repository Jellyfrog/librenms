<?php

echo 'raisecomOpticalTransceiverDDMTable ';
$pre_cache['raisecomOpticalTransceiverDDMTable'] = SnmpQuery::walk('RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverDDMTable')->table(2);
