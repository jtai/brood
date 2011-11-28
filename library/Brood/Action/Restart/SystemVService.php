<?php

namespace Brood\Action\Restart;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

class SystemVService extends AbstractAction
{
    /**
     * Name of the script in /etc/init.d to invoke
     *
     * @var string
     */
    protected $service;

    public function execute()
    {
        $verb = $this->getParameter('verb');
        if (!isset($verb[0])) {
            $verb = 'restart';
        }

        $command = sprintf('/etc/init.d/%s %s 2>&1', $this->service, $verb);

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
