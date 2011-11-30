<?php

namespace Brood\Action\Announce;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

class NewRelic extends AbstractAction
{
    protected $notified = 0;

    public function execute()
    {
        $args = array();

        $apikey = (string) $this->getRequiredParameter('api_key');
        $args[] = sprintf('-H %s', escapeshellarg('x-api-key:' . $apikey));

        $changelog = (string) $this->getParameter('changelog');
        if (!empty($changelog)) {
            $args[] = sprintf('-d %s', escapeshellarg('deployment[changelog]=' . $changelog));
        }

        $description = (string) $this->getParameter('message');
        if (!empty($description)) {
            $args[] = sprintf('-d %s', escapeshellarg('deployment[description]=' . $description));
        }

        $revision = (string) $this->getParameter('ref');
        if (!empty($revision)) {
            $args[] = sprintf('-d %s', escapeshellarg('deployment[revision]=' . $revision));
        }

        $user = (string) $this->getParameter('user');
        if (!empty($user)) {
            $args[] = sprintf('-d %s', escapeshellarg('deployment[user]=' . $user));
        }

        foreach ($this->getParameter('app_name') as $name) {
            $this->log(Logger::INFO, __CLASS__, sprintf('Sending notification to New Relic application "%s"', $name));

            $this->doRequest(array_merge($args, array(
                sprintf('-d %s', escapeshellarg('deployment[app_name]=' . $name))
            )));
        }

        foreach ($this->getParameter('application_id') as $id) {
            $this->log(Logger::INFO, __CLASS__, sprintf('Sending notification to New Relic application "%s"', $name));

            $this->doRequest(array_merge($args, array(
                sprintf('-d %s', escapeshellarg('deployment[application_id]=' . $id))
            )));
        }

        if (!$this->notified) {
            throw new \RuntimeException(sprintf('"app_name" or "application_id" configuration parameter is required by %s', get_class($this)));
        }
   }

    protected function doRequest($args)
    {
        $command = 'curl -s ' . join(' ', $args) . ' https://rpm.newrelic.com/deployments.xml';
        unset($output);

        $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));

        $this->notified++;
    }
}
