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
    protected $failedJobs = 0;

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
        $this->logger->log(Logger::NOTICE, '[overlord] ' . __CLASS__, 'Overlord starting up');

        $client = Gearman\Factory::clientFactory($this->config);

        $client->setDataCallback(array($this, 'onData'));
        $client->setCompleteCallback(array($this, 'onComplete'));
        $client->setFailCallback(array($this, 'onFail'));

        $hostGroups = $this->config->getHostGroups();
        $aliases = $this->config->getHostAliases();

        foreach ($this->config->getActions() as $actionIndex => $action) {
            // must re-fetch XML after each action; some actions may add global parameters
            $xml = $this->config->getXml();

            $workload = Gearman\Util::encodeWorkload(array($xml, $actionIndex));

            if ($action->getOverlord()) {
                $functionName = Gearman\Util::getFunctionName('overlord.local');
                $this->logger->log(Logger::NOTICE, '[overlord] ' . __CLASS__, sprintf(
                    'Dispatching %s locally',
                    $action->getClass()
                ));
                $this->logger->log(Logger::DEBUG, '[overlord] ' . __CLASS__, sprintf(
                    'Sent job to function "%s", actionIndex = %d, xml is %d bytes',
                    $functionName, $actionIndex, strlen($xml)
                ));

                // create dummy gearman job that we can call action class with
                $job = new Gearman\LocalGearmanJob();
                $job->setFunctionName($functionName);
                $job->setWorkload($workload);
                $job->setContext('overlord.local');

                $job->setDataCallback(array($this, 'onData'));
                $job->setCompleteCallback(array($this, 'onComplete'));
                $job->setFailCallback(array($this, 'onFail'));

                // disable direect printing of log messages; messages will be
                // logged via the onData callback
                $this->logger->disable();

                $job->finish(Dispatcher::dispatch($job, $this));

                // re-enable logger
                $this->logger->enable();
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
                    $this->logger->log(Logger::WARN, '[overlord] ' . __CLASS__, sprintf(
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
                    $this->logger->log(Logger::NOTICE, '[overlord] ' . __CLASS__, sprintf(
                        'Dispatching %s to %s',
                        $action->getClass(), $host
                    ));
                    $this->logger->log(Logger::DEBUG, '[overlord] ' . __CLASS__, sprintf(
                        'Sent job to function "%s", actionIndex = %d, xml is %d bytes',
                        $functionName, $actionIndex, strlen($xml)
                    ));
                    $client->addTask($functionName, $workload, isset($aliases[$host]) ? $aliases[$host] : $host);
                }

                // blocks until tasks finish
                $client->runTasks();

                if ($this->failedJobs) {
                    $this->logger->log(Logger::ERR, '[overlord] ' . __CLASS__, sprintf('%d job%s failed', $this->failedJobs, $this->failedJobs == 1 ? '' : 's'));

                    // http://xkcd.com/292
                    goto shutdown;
                }
            }
        }

        shutdown:
        $this->logger->log(Logger::NOTICE, '[overlord] ' . __CLASS__, 'Overlord shutting down');
    }

    public function onData(\GearmanTask $task, $context)
    {
        list($type, $data) = Gearman\Util::decodeData($task->data());

        switch ($type) {
            case 'log':
                list($priority, $tag, $message) = $data;
                $this->logger->log($priority, sprintf('[%s] %s', $context, $tag), $message, true);
                break;

            case 'config':
                list($param, $value) = $data;
                $this->config->addParameter($param, $value);
                break;

            default:
                $this->logger->log(Logger::WARN, sprintf('[%s] %s', $context, __CLASS__), 'Job sent unknown data', true);
                break;
        }
    }

    public function onComplete(\GearmanTask $task, $context)
    {
        $this->logger->log(Logger::INFO, sprintf('[%s] %s', $context, __CLASS__), 'Job returned success', true);
    }

    public function onFail(\GearmanTask $task, $context)
    {
        $this->logger->log(Logger::ERR, sprintf('[%s] %s', $context, __CLASS__), 'Job returned failure', true);
        $this->failedJobs++;
    }
}
