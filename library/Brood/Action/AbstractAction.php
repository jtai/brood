<?php

namespace Brood\Action;

use Brood\Config\Config,
    Brood\Log\Logger;

abstract class AbstractAction implements Action
{
    private $job;
    private $config;
    private $actionIndex;
    private $logger;

    private $action;

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
        $value = $this->action->getParameter($param);

        // look in global config if we don't find the param in the action
        if (!isset($value[0])) {
            $value = $this->config->getParameter($param);
        }

        return $value;
    }

    public function getRequiredParameter($param)
    {
        $value = $this->getParameter($param);
        if (!isset($value[0])) {
            throw new \RuntimeException(sprintf('"%s" configuration parameter is required by %s', $param, get_class($this)));
        }
        return $value;
    }

    public function sudo(&$command, &$output, &$return_var, $user = null)
    {
        // if $user is empty, just act like exec()
        if (!empty($user)) {
            $command = sprintf('sudo -u %s -- %s', escapeshellarg($user), $command);
        }

        return $this->exec($command, $output, $return_var);
    }

    public function exec(&$command, &$output, &$return_var)
    {
        $this->log(Logger::DEBUG, get_class($this), $command);

        $return = exec($command, $output, $return_var);

        foreach ($output as $line) {
            // skip empty lines
            if (trim($line) == '') {
                continue;
            }

            $this->log(Logger::DEBUG, get_class($this), $line);
        }

        if ($return_var !== 0) {
            throw new \RuntimeException(sprintf('"%s" exited %d', $command, $return_var));
        }

        return $return;
    }

    public function chdir($directory)
    {
        $success = @chdir($directory);

        if (!$success) {
            throw new \RuntimeException(sprintf('Unable to chdir to "%s"', $directory));
        }

        return $success;
    }
}
