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

    public function getRequiredParameter($param)
    {
        $value = $this->getParameter($param);
        if (!isset($value[0])) {
            throw new \RuntimeException(sprintf('"%s" configuration parameter is required by %s', $param, get_class($this->action)));
        }
        return $value;
    }

    /**
     * Wrapper for exec()
     *
     * Automatically wraps command with sudo if sudo parameter is specified in
     * configuration.
     *
     * @param string $command The command that will be executed. Passed by reference, will be modified to reflect sudo call if sudo configuration parameter is present.
     * @param array $output If the output argument is present, then the specified array will be filled with every line of output from the command.
     * @param int $return_var If the return_var argument is present along with the output argument, then the return status of the executed command will be written to this variable.
     * @param bool $handleOutputAndRetval If $handleOutputAndRetval is true (the default), output from the command will be logged as DEBUG messages. Also, if the command exists non-zero, an ERR will be logged, and sendFail() will be called on the Gearman job. Setting $handleOutputAndRetval to false disables any sort of result handling and returns immediately after calling exec().
     * @return bool True on success (command exited 0), false otherwise
     */
    public function exec(&$command, &$output, &$return_var, $handleOutputAndRetval = true)
    {
        $sudo = (string) $this->getParameter('sudo');
        if (!empty($sudo)) {
            $command = sprintf('sudo -u %s -- %s', escapeshellarg($sudo), $command);
        }

        $this->log(Logger::DEBUG, get_class($this), $command);

        exec($command, $output, $return_var);

        // if we're not handling the result, return immediately
        if (!$handleOutputAndRetval) {
            return $return_var === 0;
        }

        if ($return_var !== 0) {
            $this->log(Logger::ERR, get_class($this), sprintf('"%s" exited %d', $command, $return_var));
        }

        foreach ($output as $line) {
            $this->log(Logger::DEBUG, get_class($this), $line);
        }

        if ($return_var !== 0) {
            $this->job->sendFail();
        }

        return $return_var === 0;
    }

    public function chdir($directory, $handleOutputAndRetval = true)
    {
        $success = @chdir($directory);

        if (!$handleOutputAndRetval) {
            return $success;
        }

        if (!$success) {
            $this->log(Logger::ERR, get_class($this), sprintf('Unable to chdir to "%s"', $directory));
            $this->job->sendFail();
        }

        return $success;
    }
}
