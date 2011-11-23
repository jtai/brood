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

    public function __construct(Config $config, $logLevel = Logger::DEBUG)
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

            // build a queue of hosts for each hostgroup
            $queues = array();

            // remember max concurrency for each hostgroup
            $concurrency = array();

            foreach ($action->getHostGroups() as $hostGroupName => $hostGroupInfo) {
                $hostGroup = $hostGroups[$hostGroupName];
                foreach (array_keys($hostGroup->getHosts()) as $host) {
                    $queues[$hostGroupName][] = $host;
                }
                $concurrency[$hostGroupName] = (int) $hostGroupInfo['concurrency'];
            }

            foreach (array_keys($action->getHosts()) as $host) {
                $queues[''][] = $host;
            }
            $concurrency[''] = 0;

            while (!empty($queues)) {
                $hostsThisRun = array();

                // loop over every queue and dequeue a few hosts for this run
                foreach ($queues as $hostGroupName => $hosts) {
                    $added = 0;

                    foreach ($hosts as $i => $host) {
                        // dequeue a host
                        $hostsThisRun[] = $host;
                        unset($queues[$hostGroupName][$i]);

                        // make sure we don't go over our concurrency limit
                        $added++;
                        if ($added == $concurrency[$hostGroupName]) {
                            break;
                        }
                    }

                    // if this queue is now empty, remove it entirely
                    if (empty($hosts)) {
                        unset($queues[$hostGroupName]);
                    }
                }

                // loop over every host in this run and dispatch
                foreach ($hostsThisRun as $host) {
                    $functionName = Gearman\Util::getFunctionName($host);
                    $this->logger->log(Logger::INFO, __CLASS__, sprintf(
                        'Dispatching %s to %s',
                        $action->getClass(), $functionName
                    ));
                    $client->addTask($functionName, $workload);
                }

                // blocks until tasks finish
                $client->runTasks();
            }
        }

        $this->logger->log(Logger::INFO, __CLASS__, 'Overlord shutting down');
    }

    public function onData(\GearmanTask $task)
    {
        list($priority, $tag, $message) = $this->logger->deserializeEntry($task->data());
        $this->logger->log($priority, sprintf('%s[%s]', $tag, $task->jobHandle()), $message);
    }

    public function onComplete(\GearmanTask $task)
    {
        $this->logger->log(Logger::INFO, sprintf('%s[%s]', __CLASS__, $task->jobHandle()), 'Job returned success');
    }

    public function onFail(\GearmanTask $task)
    {
        $this->logger->log(Logger::ERR, sprintf('%s[%s]', __CLASS__, $task->jobHandle()), 'Job returned failure');
    }
}
