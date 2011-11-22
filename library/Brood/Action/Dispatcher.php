<?php

namespace Brood\Action;

use Brood\Gearman,
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
     * @param Brood\Overlord|Brood\Drone $context
     * @return mixed
     */
    public static function dispatch(\GearmanJob $job, $context)
    {
        list($configHash, $actionIndex) = explode(' ', Gearman\Util::decodeWorkload($job->workload()));

        $config = $context->getConfig();
        $logger = $context->getLogger();

        // this limitation will be removed in future versions
        if ($config->getConfigHash() != $configHash) {
            $logger->log('Configuration on overload does not match configuration on drone', Logger::ERR);
            $job->sendData($logger->serializeEntry());
            $job->sendFail();
            return;
        }

        $actions = $config->getActions();

        if (!isset($actions[$actionIndex])) {
            $logger->log(sprintf('No action with index %d', $actionIndex), Logger::ERR);
            $job->sendData($logger->serializeEntry());
            $job->sendFail();
            return;
        }

        $class = $actions[$actionIndex]->getClass();
        if (!empty($class) && $class{0} != '\\') {
            $class = '\Brood\Action\\' . $class;
        }

        // this depends on an autoloader being configured
        try {
            $action = new $class();
        } catch (\Exception $e) {
            $logger->log(sprintf('Unable to load action class "%s"', $class), Logger::ERR);
            $job->sendData($logger->serializeEntry());
            $job->sendFail();
            return;
        }

        if (!($action instanceof Action)) {
            $logger->log(sprintf('Action class "%s" does not implement Brood\Action\Action interface', $class), Logger::ERR);
            $job->sendData($logger->serializeEntry());
            $job->sendFail();
            return;
        }

        try {
            return $action->execute($job, $config, $actionIndex, $logger);
        } catch (\Exception $e) {
            $logger->log(sprintf('%s::execute() threw an exception: %s: %s', get_class($e), $e->getMessage()), Logger::ERR);
            $job->sendData($logger->serializeEntry());
            $job->sendFail();
            return;
        }
    }
}
