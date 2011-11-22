<?php

namespace Brood\Action;

use Brood\Config\Config,
    Brood\Log\Logger;

class HelloWorld implements Action
{
    public function execute(\GearmanJob $job, Config $config, $actionIndex)
    {
        $job->sendData(Logger::serialize('Hello world!', Logger::INFO));
    }
}
