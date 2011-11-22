<?php

namespace Brood\Action;

use Brood\Config\Config;

interface Action
{
    public function execute(\GearmanJob $job, Config $config, $actionIndex);
}
