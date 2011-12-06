<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Action\Announce;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

/**
 * Makes a request to New Relic's deployment API to note that a deploy occurred
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
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

        $names = $this->getParameter('app_name');
        if (isset($names[0])) {
            foreach ($names as $name) {
                $this->log(Logger::INFO, __CLASS__, sprintf('Sending notification to New Relic application "%s"', $name));

                $this->doRequest(array_merge($args, array(
                    sprintf('-d %s', escapeshellarg('deployment[app_name]=' . $name))
                )));
            }
        }

        $ids = $this->getParameter('application_id');
        if (isset($ids[0])) {
            foreach ($ids as $id) {
                $this->log(Logger::INFO, __CLASS__, sprintf('Sending notification to New Relic application "%s"', $name));

                $this->doRequest(array_merge($args, array(
                    sprintf('-d %s', escapeshellarg('deployment[application_id]=' . $id))
                )));
            }
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
