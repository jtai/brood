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

    public function log($message, $priority)
    {
        printf("%s: %s\n", $this->translatePriority($priority), $message);
        $this->lastEntry = array($message, $priority);
    }

    public function serializeEntry($message = null, $priority = null)
    {
        if ($message === null && $priority === null) {
            list($message, $priority) = $this->lastEntry;
        }

        return $priority . ' ' . $message;
    }

    public function deserializeEntry($serialized)
    {
        return array_reverse(explode(' ', $serialized, 2));
    }

    protected function translatePriority($priority)
    {
        $priorities = array('EMERG', 'ALERT', 'CRIT', 'ERR', 'WARN', 'NOTICE', 'INFO', 'DEBUG');
        return $priorities[$priority];
    }
}
