<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Drone
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Drone;

use Brood\Config\Config,
    Brood\Gearman,
    Brood\Log\Logger as Logger;

/**
 * Perform actions
 *
 * The Drone connects to the Gearman job server and waits for instructions from
 * the Overlord. When the Drone receives an action from the Overlord, it
 * performs the action and exits.
 *
 * @category   Brood
 * @package    Brood_Drone
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class Drone
{
    protected $config;
    protected $logger;

    public function __construct(Config $config, $logLevel = Logger::INFO)
    {
        $this->config = $config;
        $this->logger = new Logger($logLevel);

    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function run()
    {
        $this->logger->log(Logger::NOTICE, __CLASS__, sprintf(
            'Drone starting up on %s', $this->getHostname()
        ));

        $worker = Gearman\Factory::workerFactory($this->config);

        $functionName = Gearman\Util::getFunctionName($this->getHostname());
        $callback = array('\Brood\Action\Dispatcher', 'dispatch');
        $worker->addFunction($functionName, $callback, $this);

        $this->logger->log(Logger::DEBUG, __CLASS__, sprintf(
            'Connecting to %s, waiting for a %s job',
            join(',', array_keys($this->config->getGearmanServers())), $functionName
        ));

        // don't loop -- exit after every job and let supervisord restart us
        $worker->work();

        // pause a bit to ensure job status gets sent to gearmand
        sleep(1);

        switch ($worker->returnCode()) {
            case \GEARMAN_SUCCESS:
                // will get here even if job returns failure
                $this->logger->log(Logger::NOTICE, __CLASS__, 'Drone shutting down, job finished');
                return true;
                break;

            case \GEARMAN_TIMEOUT:
                $this->logger->log(Logger::NOTICE, __CLASS__, 'Drone shutting down, timed out waiting for job');
                return true;
                break;

            default:
                $this->logger->log(Logger::ERR, __CLASS__, $worker->error());
                return false;
                break;
        }
    }

    public function getHostname()
    {
        $hostname = (string) $this->config->getParameter('hostname');

        if (!empty($hostname)) {
            return $hostname;
        }

        return gethostname();
    }
}
