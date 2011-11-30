<?php

namespace Brood\Overlord;

use Brood\Log\Logger;

class Options
{
    protected $options;

    protected function parse()
    {
        if ($this->options === null) {
            $this->options = getopt('c:hl:m:o:p:r:u:', array('config:', 'help', 'log-level:', 'message:', 'log-file:', 'prev-ref:', 'ref:', 'user:'));
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
usage: php overlord.php [options]

OPTIONS
  -c, --config     path to config file, defaults to "brood.xml" in brood distribution root
  -h, --help       print this help message
  -l, --log-level  log level for stdout, one of: ERR, WARN, NOTICE, INFO, or DEBUG, defaults to INFO
  -m, --message    deploy message, primarily used by announce actions
  -o, --log-file   write DEBUG output to specified log file
  -p, --prev-ref   previous ref, primarily used by announce actions to link to diffs
  -r, --ref        ref to deploy, defaults to HEAD
  -u, --user       user performing deploy, primarily used by announce actions

EOF;
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

    public function getMessage()
    {
        $options = $this->parse();

        if (isset($options['message'])) {
            return $options['message'];
        }

        if (isset($options['m'])) {
            return $options['m'];
        }
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

    public function getPrevRef()
    {
        $options = $this->parse();

        if (isset($options['prev-ref'])) {
            return $options['prev-ref'];
        }

        if (isset($options['p'])) {
            return $options['p'];
        }
    }

    public function getRef()
    {
        $options = $this->parse();

        if (isset($options['ref'])) {
            return $options['ref'];
        }

        if (isset($options['r'])) {
            return $options['r'];
        }
    }

    public function getUser()
    {
        $options = $this->parse();

        if (isset($options['user'])) {
            return $options['user'];
        }

        if (isset($options['u'])) {
            return $options['u'];
        }

        return posix_getlogin();
    }
}
