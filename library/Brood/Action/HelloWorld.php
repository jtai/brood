<?php

namespace Brood\Action;

use Brood\Config\Config,
    Brood\Log\Logger;

class HelloWorld implements Action
{
    public function execute(\GearmanJob $job, Config $config, $actionIndex, Logger $logger)
    {
        $logger->log('Hello world!', Logger::INFO);
        $job->sendData($logger->serializeEntry());
    }
}
