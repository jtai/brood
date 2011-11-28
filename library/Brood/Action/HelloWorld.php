<?php

namespace Brood\Action;

use Brood\Config\Config,
    Brood\Log\Logger;

class HelloWorld extends AbstractAction
{
    public function execute(\GearmanJob $job, Config $config, $actionIndex, Logger $logger)
    {
        $logger->log(Logger::INFO, __CLASS__, 'Hello world!');
        $job->sendData($logger->serializeEntry());
    }
}
