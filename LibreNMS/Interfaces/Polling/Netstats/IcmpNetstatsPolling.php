<?php

namespace LibreNMS\Interfaces\Polling\Netstats;

interface IcmpNetstatsPolling
{
    /** @param array<mixed> $oids */
    public function pollIcmpNetstats(array $oids): array;
}
