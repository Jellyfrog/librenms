<?php

namespace LibreNMS\Interfaces\Polling\Netstats;

interface IpForwardNetstatsPolling
{
    /** @param array<mixed> $oids */
    public function pollIpForwardNetstats(array $oids): array;
}
