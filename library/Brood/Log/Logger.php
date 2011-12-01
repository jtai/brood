<?php

namespace Brood\Log;

class Logger
{
    const ERR     = 3;  // Error: error conditions
    const WARN    = 4;  // Warning: warning conditions
    const NOTICE  = 5;  // Notice: normal but significant condition
    const INFO    = 6;  // Informational: informational messages
    const DEBUG   = 7;  // Debug: debug messages

    protected $logLevel;
    protected $disabled = false;

    public function __construct($logLevel = self::DEBUG)
    {
        $this->setLogLevel($logLevel);
    }

    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
        return $this;
    }

    public function disable()
    {
        $this->disabled = true;
        return $this;
    }

    public function enable()
    {
        $this->disabled = false;
        return $this;
    }

    public function log($priority, $tag, $message, $force = false)
    {
        if ($force) {
            $print = true;
        } else {
            $print = !$this->disabled;
        }

        if ($print && $priority <= $this->logLevel) {
            printf("%s %s %s: %s\n", date('M d H:i:s'), $this->translatePriority($priority), $tag, $message);
        }
    }

    protected function translatePriority($priority)
    {
        $priorities = array(3 => 'EE', 'WW', 'NN', 'II', '**');
        return $priorities[$priority];
    }
}
