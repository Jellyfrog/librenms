<?php

namespace LibreNMS\Interfaces\Polling\Netstats;

interface IpNetstatsPolling
{
    /** @param array<mixed> $oids */
    public function pollIpNetstats(array $oids): array;
}
