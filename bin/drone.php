<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

/**
 */
include dirname(__DIR__) . '/library/Brood/Loader/Autoloader.php';
$autoloader = new \Brood\Loader\Autoloader();
$autoloader->register();

$options = new \Brood\Drone\Options();
if ($options->getHelp()) {
    die($options->getUsage());
}

$config = new \Brood\Config\Config(file_get_contents($options->getConfig()));
if ($options->getHostname()) {
    $config->addParameter('hostname', $options->getHostname());
}

$drone = new \Brood\Drone\Drone($config, $options->getLogLevel());
$drone->run();

