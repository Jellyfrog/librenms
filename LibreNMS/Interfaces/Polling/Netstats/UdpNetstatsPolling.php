<?php

namespace LibreNMS\Interfaces\Polling\Netstats;

interface UdpNetstatsPolling
{
    /** @param array<mixed> $oids */
    public function pollUdpNetstats(array $oids): array;
}
