<?php

namespace Brood\Log;

class Logger
{
    const ERR     = 3;  // Error: error conditions
    const WARN    = 4;  // Warning: warning conditions
    const NOTICE  = 5;  // Notice: normal but significant condition
    const INFO    = 6;  // Informational: informational messages
    const DEBUG   = 7;  // Debug: debug messages

    protected $lastEntry;
    protected $logLevel;

    public function __construct($logLevel = self::DEBUG)
    {
        $this->setLogLevel($logLevel);
    }

    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
        return $this;
    }

    public function log($priority, $tag, $message)
    {
        $this->lastEntry = array($priority, $tag, $message);

        if ($priority <= $this->logLevel) {
            printf("%s %s %s: %s\n", date('M d H:i:s'), $this->translatePriority($priority), $tag, $message);
        }
    }

    public function serializeEntry($priority = null, $tag = null, $message = null)
    {
        if ($priority === null && $tag === null && $message === null) {
            list($priority, $tag, $message) = $this->lastEntry;
        }

        return serialize(array($priority, $tag, $message));
    }

    public function deserializeEntry($serialized)
    {
        return unserialize($serialized);
    }

    protected function translatePriority($priority)
    {
        $priorities = array(3 => 'EE', 'WW', 'NN', 'II', '**');
        return $priorities[$priority];
    }
}
