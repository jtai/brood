<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Action;

use Brood\Gearman,
    Brood\Config\Config,
    Brood\Log\Logger as Logger;

/**
 * Action dispatcher
 *
 * Provides a callback for GearmanWorker::addFunction(). The callback attempts
 * to load and instantiate the action class specified by the Overlord, call
 * setContext() on it, then call execute() on it.
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class Dispatcher
{
    /**
     * Callback for GearmanWorker::addFunction()
     *
     * A Gearman worker calls this method when a job arrives. We get the Drone
     * or Overlord object as the context argument; this is set up by the worker
     * before a job arrives. Using the information in the job workload, we
     * figure out what class we need to instantiate, then call the class'
     * setContext() and execute() methods.
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
            if ($file{0} != '/') {
                $root = dirname(dirname(dirname(__DIR__)));
                $file = $root . '/' . $file;
            }

            // returns canonicalized absolute pathname, or false if the file does not exist
            $file = realpath($file);

            if (!$file) {
                self::logFailure(Logger::DEBUG, __CLASS__, sprintf('Unable to load "%s"', $file), $logger, $job);
                return;
            }

            self::log(Logger::DEBUG, __CLASS__, sprintf('Loading "%s"', $file), $logger, $job);
            include $file;
        }

        $class = $actions[$actionIndex]->getClass();
        if (!empty($class) && $class{0} != '\\') {
            $class = '\\' . $class;
        }

        if (!class_exists($class)) {
            self::logFailure(Logger::ERR, __CLASS__, sprintf('Action class "%s" does not exist', $class), $logger, $job);
            return;
        }

        try {
            $action = new $class();
        } catch (\Exception $e) {
            self::logFailure(Logger::ERR, __CLASS__, sprintf('Unable to instantiate action class "%s": %s: %s', $class, get_class($e), $e->getMessage()), $logger, $job);
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
