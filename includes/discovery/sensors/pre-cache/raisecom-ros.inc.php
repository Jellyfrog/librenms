<?php

echo 'rosMgmtOpticalTransceiverDDMTable ';
$pre_cache['rosMgmtOpticalTransceiverDDMTable'] = SnmpQuery::hideMib()->walk('ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverDDMTable')->table(2);
