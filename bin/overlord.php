<?php

include dirname(__DIR__) . '/library/Brood/Loader/Autoloader.php';
$autoloader = new \Brood\Loader\Autoloader();
$autoloader->register();

$options = new \Brood\Overlord\Options();
if ($options->getHelp()) {
    die($options->getUsage());
}

$config = new \Brood\Config\Config(file_get_contents($options->getConfig()));
if ($options->getMessage()) {
    $config->addParameter('message', $options->getMessage());
}
if ($options->getPrevRef()) {
    $config->addParameter('prev_ref', $options->getPrevRef());
}
if ($options->getRef()) {
    $config->addParameter('ref', $options->getRef());
}

$overlord = new \Brood\Overlord\Overlord($config, $options->getLogLevel());
$overlord->run();

