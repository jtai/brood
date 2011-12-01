<?php

namespace Brood\Overlord;

use Brood\Config\Config,
    Brood\Gearman,
    Brood\Action\Dispatcher,
    Brood\Log\Logger;

class Overlord
{
    protected $config;
    protected $logger;
    protected $failedJobs = false;

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
        $this->logger->log(Logger::NOTICE, __CLASS__, 'Overlord starting up');

        $client = Gearman\Factory::clientFactory($this->config);

        $client->setDataCallback(array($this, 'onData'));
        $client->setCompleteCallback(array($this, 'onComplete'));
        $client->setFailCallback(array($this, 'onFail'));

        $xml = $this->config->getXml();
        $hostGroups = $this->config->getHostGroups();

        foreach ($this->config->getActions() as $actionIndex => $action) {
            $workload = Gearman\Util::encodeWorkload(array($xml, $actionIndex));

            if ($action->getOverlord()) {
                $functionName = Gearman\Util::getFunctionName('overlord');
                $this->logger->log(Logger::NOTICE, __CLASS__, sprintf(
                    'Dispatching %s locally',
                    $action->getClass()
                ));
                $this->logger->log(Logger::DEBUG, __CLASS__, sprintf(
                    'Sent job to function "%s", actionIndex = %d, xml is %d bytes',
                    $functionName, $actionIndex, strlen($xml)
                ));

                // create dummy gearman job that we can call action class with
                $job = new Gearman\LocalGearmanJob();
                $job->setFunctionName($functionName);
                $job->setWorkload($workload);

                // don't set data callback, otherwise all local jobs will be logged twice --
                // once directly, and once through the data callback
                //$job->setDataCallback(array($this, 'onData'));
                $job->setCompleteCallback(array($this, 'onComplete'));
                $job->setFailCallback(array($this, 'onFail'));

                $job->finish(Dispatcher::dispatch($job, $this));
            }

            if ($this->failedJobs) {
                break;
            }

            // build a queue of hosts for each hostgroup
            $queues = array();

            // remember max concurrency for each hostgroup
            $concurrency = array();

            foreach ($action->getHostGroups() as $hostGroupName => $hostGroupInfo) {
                if (!isset($hostGroups[$hostGroupName])) {
                    $this->logger->log(Logger::WARN, __CLASS__, sprintf(
                        'Host group "%s" does not exist, referenced by %s',
                        $hostGroupName, $action->getClass()
                    ));
                    continue;
                }

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
                    $this->logger->log(Logger::NOTICE, __CLASS__, sprintf(
                        'Dispatching %s to %s',
                        $action->getClass(), $host
                    ));
                    $this->logger->log(Logger::DEBUG, __CLASS__, sprintf(
                        'Sent job to function "%s", actionIndex = %d, xml is %d bytes',
                        $functionName, $actionIndex, strlen($xml)
                    ));
                    $client->addTask($functionName, $workload);
                }

                // blocks until tasks finish
                $client->runTasks();

                if ($this->failedJobs) {
                    // http://xkcd.com/292
                    goto shutdown;
                }
            }
        }

        shutdown:
        $this->logger->log(Logger::NOTICE, __CLASS__, 'Overlord shutting down');
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
        $this->failedJobs = true;
    }
}
