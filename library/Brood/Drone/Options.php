<?php

namespace Brood\Drone;

use Brood\Log\Logger;

class Options
{
    protected $options;

    protected function parse()
    {
        if ($this->options === null) {
            $this->options = getopt('c:hH:l:o:', array('config:', 'help', 'hostname:', 'log-level:', 'log-file:'));
        }

        return $this->options;
    }

    public function getConfig()
    {
        $options = $this->parse();

        if (isset($options['config'])) {
            return $options['config'];
        }

        if (isset($options['c'])) {
            return $options['c'];
        }

        return dirname(dirname(dirname(__DIR__))) . '/brood.xml';
    }

    public function getHelp()
    {
        $options = $this->parse();
        return (isset($options['help']) || isset($options['h']));
    }

    public function getUsage()
    {
        return <<<EOF
usage: php drone.php [options]

OPTIONS
  -c, --config     path to config file, defaults to "brood.xml" in brood distribution root
  -h, --help       print this help message
  -H, --hostname   override hostname, used by drone to determine job name
  -l, --log-level  log level for stdout, one of: ERR, WARN, NOTICE, INFO, or DEBUG, defaults to INFO
  -o, --log-file   write DEBUG output to specified log file

EOF;
    }

    public function getHostname()
    {
        $options = $this->parse();

        if (isset($options['hostname'])) {
            return $options['hostname'];
        }

        if (isset($options['H'])) {
            return $options['H'];
        }
    }

    public function getLogLevel()
    {
        $options = $this->parse();
        $reflection = new \ReflectionObject(new Logger());

        if (isset($options['log-level'])) {
            return $reflection->getConstant($options['log-level']);
        }

        if (isset($options['l'])) {
            return $reflection->getConstant($options['l']);
        }

        return Logger::INFO;
    }

    public function getLogFile()
    {
        $options = $this->parse();

        if (isset($options['log-file'])) {
            return $options['log-file'];
        }

        if (isset($options['o'])) {
            return $options['o'];
        }
    }
}
