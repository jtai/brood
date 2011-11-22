<?php

namespace Brood\Action;

use Brood\Config\Config,
    Brood\Log\Logger;

interface Action
{
    public function execute(\GearmanJob $job, Config $config, $actionIndex, Logger $logger);
}
