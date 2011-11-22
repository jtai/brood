<?php

namespace Brood\Gearman;

use Brood\Config\Config;

class Factory
{
    public static function workerFactory(Config $config)
    {
        return self::factory('\GearmanWorker', $config);
    }

    public static function clientFactory(Config $config)
    {
        return self::factory('\GearmanClient', $config);
    }

    protected static function factory($class, Config $config)
    {
        $object = new $class();

        $servers = array_keys($config->getGearmanServers());
        $object->addServers(join(',', $servers));

        $timeout = $config->getGearmanTimeout();
        if ($timeout !== null) {
            $object->setTimeout($timeout);
        }

        return $object;
    }
}
