<?php

namespace LibreNMS\Interfaces\Polling\Netstats;

interface SnmpNetstatsPolling
{
    /** @param array<mixed> $oids */
    public function pollSnmpNetstats(array $oids): array;
}
