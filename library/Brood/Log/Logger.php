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

    public static function serialize($message, $priority)
    {
        return $priority . ' ' . $message;
    }

    public static function deserialize($serialized)
    {
        return array_reverse(explode(' ', $serialized, 2));
    }
}
