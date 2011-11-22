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

    public function __construct(Config $config, $hostname)
    {
        $this->config = $config;
        $this->logger = new Logger();
        $this->hostname = $hostname;
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
        $this->logger->log(sprintf('Drone starting up on %s', $this->hostname), Logger::INFO);

        $worker = Gearman\Factory::workerFactory($this->config);

        $functionName = Gearman\Util::getFunctionName($this->hostname);
        $callback = array('\Brood\Action\Dispatcher', 'dispatch');
        $worker->addFunction($functionName, $callback, $this);

        $this->logger->log(sprintf('Connecting to %s and waiting for a %s job', join(',', array_keys($this->config->getGearmanServers())), $functionName), Logger::INFO);

        // don't loop -- exit after every job and let supervisord restart us
        $worker->work();

        switch ($worker->returnCode()) {
            case \GEARMAN_SUCCESS:
                // will get here even if job returns failure
                $this->logger->log('Drone shutting down, job finished', Logger::INFO);
                break;

            case \GEARMAN_TIMEOUT:
                $this->logger->log('Drone shutting down, timed out waiting for job', Logger::INFO);
                break;

            default:
                $this->logger->log($worker->error(), Logger::ERR);
                break;
        }
    }
}
