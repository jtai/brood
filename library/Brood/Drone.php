<?php

namespace Brood;

use Brood\Config\Config,
    Brood\Gearman,
    Brood\Log\Logger as Logger;

class Drone
{
    protected $config;
    protected $logger;
    protected $hostname;

    public function __construct(Config $config, $logLevel = Logger::DEBUG, $hostname = null)
    {
        $this->config = $config;
        $this->logger = new Logger($logLevel);
        $this->hostname = $hostname === null ? gethostname() : $hostname;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getHostname()
    {
        return $this->hostname;
    }

    public function run()
    {
        $this->logger->log(Logger::NOTICE, __CLASS__, sprintf(
            'Drone starting up on %s', $this->hostname
        ));

        $worker = Gearman\Factory::workerFactory($this->config);

        $functionName = Gearman\Util::getFunctionName($this->hostname);
        $callback = array('\Brood\Action\Dispatcher', 'dispatch');
        $worker->addFunction($functionName, $callback, $this);

        $this->logger->log(Logger::NOTICE, __CLASS__, sprintf(
            'Connecting to %s and waiting for a %s job',
            join(',', array_keys($this->config->getGearmanServers())), $functionName
        ));

        // don't loop -- exit after every job and let supervisord restart us
        $worker->work();

        switch ($worker->returnCode()) {
            case \GEARMAN_SUCCESS:
                // will get here even if job returns failure
                $this->logger->log(Logger::NOTICE, __CLASS__, 'Drone shutting down, job finished');
                break;

            case \GEARMAN_TIMEOUT:
                $this->logger->log(Logger::NOTICE, __CLASS__, 'Drone shutting down, timed out waiting for job');
                break;

            default:
                $this->logger->log(Logger::ERR, __CLASS__, $worker->error());
                break;
        }
    }
}