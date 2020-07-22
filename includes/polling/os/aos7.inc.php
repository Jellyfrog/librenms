<?php

if (strpos($device['sysDescr'], 'Enterprise')) {
    [,,$hardware,$version] = explode(' ', $device['sysDescr']);
} else {
    [,$hardware,$version] = explode(' ', $device['sysDescr']);
}
