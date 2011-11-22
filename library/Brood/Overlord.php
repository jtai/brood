<?php

namespace Brood;

use Brood\Config\Config,
    Brood\Gearman,
    Brood\Action\Dispatcher,
    Brood\Log\Logger;

class Overlord
{
    protected $config;
    protected $logger;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->logger = new Logger();
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
        $this->logger->log(Logger::INFO, __CLASS__, 'Overlord starting up');

        $client = Gearman\Factory::clientFactory($this->config);

        $client->setDataCallback(array($this, 'onData'));
        $client->setCompleteCallback(array($this, 'onComplete'));
        $client->setFailCallback(array($this, 'onFail'));

        $configHash = $this->config->getConfigHash();
        $hostGroups = $this->config->getHostGroups();

        foreach ($this->config->getActions() as $actionIndex => $action) {
            $workload = Gearman\Util::encodeWorkload($configHash . ' ' . $actionIndex);

            if ($action->getOverlord()) {
                // FIXME: dispatch locally
            }

            // FIXME: do crazy scheduling here

            foreach ($action->getHostGroups() as $hostGroupName => $hostGroupInfo) {
                $hostGroup = $hostGroups[$hostGroupName];
                foreach (array_keys($hostGroup->getHosts()) as $host) {
                    $functionName = Gearman\Util::getFunctionName($host);
                    $this->logger->log(Logger::INFO, __CLASS__, sprintf(
                        'Dispatching %s to %s (member of hostgroup %s)',
                        $action->getClass(), $functionName, $hostGroupName
                    ));
                    $client->addTask($functionName, $workload);
                }
            }

            foreach (array_keys($action->getHosts()) as $host) {
                // create a unique function name using drone's hostname and hash of config file
                $functionName = Gearman\Util::getFunctionName($host);
                $this->logger->log(Logger::INFO, __CLASS__, sprintf(
                    'Dispatching %s to %s',
                    $action->getClass(), $functionName
                ));
                $client->addTask($functionName, $workload);
            }
        }

        // blocks until tasks finish
        $client->runTasks();
    }

    public function onData(\GearmanTask $task)
    {
        list($priority, $tag, $message) = $this->logger->deserializeEntry($task->data());
        $this->logger->log($priority, sprintf('(%s) %s', $task->jobHandle(), $tag), $message);
    }

    public function onComplete(\GearmanTask $task)
    {
        $this->logger->log(Logger::INFO, sprintf('(%s) %s', $task->jobHandle(), __CLASS__), 'Job returned success');
    }

    public function onFail(\GearmanTask $task)
    {
        $this->logger->log(Logger::ERR, sprintf('(%s) %s', $task->jobHandle(), __CLASS__), 'Job returned failure');
    }
}
