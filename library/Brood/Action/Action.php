<?php

namespace Brood\Action;

use Brood\Config\Config,
    Brood\Log\Logger;

interface Action
{
    public function setContext(\GearmanJob $job, Config $config, $actionIndex, Logger $logger);
    public function execute();
}
