<?php

echo 'rosMgmtOpticalTransceiverDDMTable ';
$pre_cache['rosMgmtOpticalTransceiverDDMTable'] = SnmpQuery::walk('ROSMGMT-OPTICAL-TRANSCEIVER-MIB::rosMgmtOpticalTransceiverDDMTable')->table(2);
