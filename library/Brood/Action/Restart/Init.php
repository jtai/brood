<?php

namespace Brood\Action\Restart;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

class Init extends AbstractAction
{
    /**
     * Name of the script in /etc/init.d to invoke
     *
     * @var string
     */
    protected $service;

    public function execute()
    {
        $command = sprintf('/etc/init.d/%s restart 2>&1', $this->service);

        exec($command, $output, $retval);

        if ($retval !== 0) {
            $this->log(Logger::ERR, __CLASS__, sprintf('"%s" exited %d', $command, $retval));
        }

        foreach ($output as $line) {
            $this->log(Logger::DEBUG, __CLASS__, $line);
        }

        if ($retval !== 0) {
            $this->job->sendFail();
            return;
        }
    }
}
