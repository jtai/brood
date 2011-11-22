<?php

namespace Brood\Log;

class Logger
{
    const EMERG   = 0;  // Emergency: system is unusable
    const ALERT   = 1;  // Alert: action must be taken immediately
    const CRIT    = 2;  // Critical: critical conditions
    const ERR     = 3;  // Error: error conditions
    const WARN    = 4;  // Warning: warning conditions
    const NOTICE  = 5;  // Notice: normal but significant condition
    const INFO    = 6;  // Informational: informational messages
    const DEBUG   = 7;  // Debug: debug messages

    protected $lastEntry;

    public function log($priority, $tag, $message)
    {
        printf("[%s] %s: %s\n", $this->translatePriority($priority), $tag, $message);
        $this->lastEntry = array($priority, $tag, $message);
    }

    public function serializeEntry($priority = null, $tag = null, $message = null)
    {
        if ($priority === null && $tag === null && $message === null) {
            list($priority, $tag, $message) = $this->lastEntry;
        }

        return join(' ', array($priority, $tag, $message));
    }

    public function deserializeEntry($serialized)
    {
        return explode(' ', $serialized, 3);
    }

    protected function translatePriority($priority)
    {
        $priorities = array('EMERG', 'ALERT', 'CRIT', 'ERR', 'WARN', 'NOTICE', 'INFO', 'DEBUG');
        return $priorities[$priority];
    }
}
