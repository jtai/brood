<?php

namespace Brood\Action;

use Brood\Gearman,
    Brood\Config\Config,
    Brood\Log\Logger as Logger;

class Dispatcher
{
    /**
     * Callback for GearmanWorker::addFunction()
     *
     * A Gearman worker calls this method when a job arrives. We get the Brood
     * config as the context argument; this is set up by the worker before a
     * job arrives. Using the information in the job workload, we figure out
     * what class we need to instantiate, then call the class' execute() method.
     *
     * @param GearmanJob $job
     * @param Brood\Overlord\Overlord|Brood\Drone\Drone $context
     * @return mixed
     */
    public static function dispatch(\GearmanJob $job, $context)
    {
        list($xml, $actionIndex) = Gearman\Util::decodeWorkload($job->workload());

        $logger = $context->getLogger();

        self::log(Logger::DEBUG, __CLASS__, sprintf('Received job, actionIndex = %d, xml is %d bytes', $actionIndex, strlen($xml)), $logger, $job);

        $config = new Config($xml);
        $actions = $config->getActions();

        if (!isset($actions[$actionIndex])) {
            self::logFailure(Logger::ERR, __CLASS__, sprintf('No action with index %d', $actionIndex), $logger, $job);
            return;
        }

        $file = $actions[$actionIndex]->getFile();
        if (!empty($file)) {
            if ($file{0} == '/') {
                include $file;
            } else {
                $root = dirname(dirname(dirname(__DIR__)));
                include $root . '/' . $file;
            }
        }

        $class = $actions[$actionIndex]->getClass();
        if (!empty($class) && $class{0} != '\\') {
            $class = '\\' . $class;
        }

        try {
            $action = new $class();
        } catch (\Exception $e) {
            self::logFailure(Logger::ERR, __CLASS__, sprintf('Unable to load action class "%s": %s: %s', $class, get_class($e), $e->getMessage()), $logger, $job);
            return;
        }

        if (!($action instanceof Action)) {
            self::logFailure(Logger::ERR, __CLASS__, sprintf('Action class "%s" does not implement Brood\Action\Action interface', $class), $logger, $job);
            return;
        }

        try {
            $action->setContext($job, $config, $actionIndex, $logger);
        } catch (\Exception $e) {
            self::logFailure(Logger::ERR, __CLASS__, sprintf('%s::setContext() threw an exception: %s: %s', $class, get_class($e), $e->getMessage()), $logger, $job);
            return;
        }

        try {
            return $action->execute();
        } catch (\Exception $e) {
            self::logFailure(Logger::ERR, __CLASS__, sprintf('%s::execute() threw an exception: %s: %s', $class, get_class($e), $e->getMessage()), $logger, $job);
            return;
        }
    }

    public static function log($priority, $tag, $message, $logger, $job)
    {
        $logger->log($priority, $tag, $message);
        $job->sendData(Gearman\Util::encodeData('log', array($priority, $tag, $message)));
    }

    public static function logFailure($priority, $tag, $message, $logger, $job)
    {
        self::log($priority, $tag, $message, $logger, $job);
        $job->sendFail();
    }
}
