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
        $config = $context->getConfig();
        $logger = $context->getLogger();

        $logger->log(Logger::DEBUG, __CLASS__, sprintf('Received workload "%s" at function "%s"', $job->workload(), $job->functionName()));
        $job->sendData($logger->serializeEntry());

        list($configHash, $actionIndex) = explode(' ', Gearman\Util::decodeWorkload($job->workload()));

        // this limitation will be removed in future versions
        if ($config->getConfigHash() != $configHash) {
            $logger->log(Logger::ERR, __CLASS__, 'Configuration on overload does not match configuration on drone');
            $job->sendData($logger->serializeEntry());
            $job->sendFail();
            return;
        }

        $actions = $config->getActions();

        if (!isset($actions[$actionIndex])) {
            $logger->log(Logger::ERR, __CLASS__, sprintf('No action with index %d', $actionIndex));
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
            $logger->log(Logger::ERR, __CLASS__, sprintf('Unable to load action class "%s": %s: %s', $class, get_class($e), $e->getMessage()));
            $job->sendData($logger->serializeEntry());
            $job->sendFail();
            return;
        }

        if (!($action instanceof Action)) {
            $logger->log(Logger::ERR, __CLASS__, sprintf('Action class "%s" does not implement Brood\Action\Action interface', $class));
            $job->sendData($logger->serializeEntry());
            $job->sendFail();
            return;
        }

        try {
            return $action->execute($job, $config, $actionIndex, $logger);
        } catch (\Exception $e) {
            $logger->log(Logger::ERR, __CLASS__, sprintf('%s::execute() threw an exception: %s: %s', get_class($e), $e->getMessage()));
            $job->sendData($logger->serializeEntry());
            $job->sendFail();
            return;
        }
    }
}
