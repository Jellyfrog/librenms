<?php

namespace LibreNMS\Data\Source\Snmp;

/**
 * Open \SNMP objects for the current poll, one per target, config and context.
 * librenms-snmp keeps the net-snmp session open on the object, so v3 discovery and sockets are reused.
 */
class PhpSnmpSessions
{
    /** @var array<string, \SNMP> */
    private array $sessions = [];

    /**
     * @param  \Closure(): ?\SNMP  $make
     */
    public function get(string $key, \Closure $make): ?\SNMP
    {
        if (! isset($this->sessions[$key])) {
            $snmp = $make();
            if ($snmp === null) {
                return null;
            }
            $this->sessions[$key] = $snmp;
        }

        return $this->sessions[$key];
    }

    /**
     * Close all sessions. Never throws: it runs in finally blocks of the poll and discovery jobs.
     */
    public function flush(): void
    {
        $sessions = $this->sessions;
        $this->sessions = []; // detach first, a failing close() must not leave sessions behind

        foreach ($sessions as $snmp) {
            try {
                $snmp->close();
            } catch (\Throwable) {
                // the object is dropped anyway, its session is freed with it
            }
        }
    }

    public function count(): int
    {
        return count($this->sessions);
    }
}
