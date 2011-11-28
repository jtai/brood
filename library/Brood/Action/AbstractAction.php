<?php

namespace Brood\Action;

use Brood\Config\Config,
    Brood\Log\Logger;

abstract class AbstractAction implements Action
{
    protected $job;
    protected $config;
    protected $actionIndex;
    protected $logger;

    protected $action;

    public function setContext(\GearmanJob $job, Config $config, $actionIndex, Logger $logger)
    {
        $this->job = $job;
        $this->config = $config;
        $this->actionIndex = $actionIndex;
        $this->logger = $logger;

        // set action for convenience
        $actions = $config->getActions();
        $this->action = $actions[$actionIndex];
    }

    public function log($priority, $tag, $message)
    {
        $this->logger->log($priority, $tag, $message);
        $this->job->sendData($this->logger->serializeEntry());
    }

    public function getParameter($param)
    {
        $parameters = $this->action->getParameters();
        if (isset($parameters)) {
            return $parameters->$param;
        }
    }

    public function getRequiredParam($param)
    {
        $value = $this->getParameter($param);
        if (!isset($value[0])) {
            throw new \RuntimeException(sprintf('"%s" configuration parameter is required by %s', $param, get_class($this->action)));
        }
        return $value;
    }
}
