<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Gearman
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Gearman;

use Brood\Config\Config;

/**
 * Factory
 *
 * Factory class to instantiate and configure GearmanWorker and GearmanClient
 * objects. These classes are supplied by the Gearman PHP extension.
 *
 * @category   Brood
 * @package    Brood_Gearman
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
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
