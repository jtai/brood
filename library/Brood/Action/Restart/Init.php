<?php

namespace Brood\Action\Restart;

use Brood\Action\AbstractAction,
    Brood\Config\Config,
    Brood\Log\Logger;

class Init extends AbstractAction
{
    /**
     * Name of the script in /etc/init.d to invoke
     *
     * @var string
     */
    protected $service;

    public function execute(\GearmanJob $job, Config $config, $actionIndex, Logger $logger)
    {
        $command = sprintf('/etc/init.d/%s restart 2>&1', $this->service);

        exec($command, $output, $retval);

        if ($retval !== 0) {
            $logger->log(Logger::ERR, __CLASS__, sprintf('"%s" exited %d', $command, $retval));
            $job->sendData($logger->serializeEntry());
        }

        foreach ($output as $line) {
            $logger->log(Logger::DEBUG, __CLASS__, $line);
            $job->sendData($logger->serializeEntry());
        }

        if ($retval !== 0) {
            $job->sendFail();
            return;
        }
    }
}
