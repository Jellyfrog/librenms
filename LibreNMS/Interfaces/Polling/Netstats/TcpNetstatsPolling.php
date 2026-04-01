<?php

namespace LibreNMS\Interfaces\Polling\Netstats;

interface TcpNetstatsPolling
{
    /** @param array<mixed> $oids */
    public function pollTcpNetstats(array $oids): array;
}
