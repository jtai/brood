<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Action\Restart;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

/**
 * Action class to restart a supervisord service
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class SupervisordService extends AbstractAction
{
    public function execute()
    {
        $args = array();

        $configuration = (string) $this->getParameter('configuration');
        if (!empty($configuration)) {
            $args[] = sprintf('-c %s', escapeshellarg($configuration));
        }

        $serverurl = (string) $this->getParameter('serverurl');
        if (!empty($serverurl)) {
            $args[] = sprintf('-s %s', escapeshellarg($serverurl));
        }

        $username = (string) $this->getParameter('username');
        if (!empty($username)) {
            $args[] = sprintf('-u %s', escapeshellarg($username));
        }

        $password = (string) $this->getParameter('password');
        if (!empty($password)) {
            $args[] = sprintf('-p %s', escapeshellarg($password));
        }

        $verb = (string) $this->getParameter('verb');
        if (empty($verb)) {
            $verb = 'restart';
        }

        if ($verb == 'stop') {
            $presentParticiple = 'stopping';
        } else {
            $presentParticiple = $verb . 'ing';
        }

        foreach ($this->getRequiredParameter('service') as $service) {
            $this->log(Logger::INFO, __CLASS__, sprintf('%s %s', ucfirst($presentParticiple), $service));

            $command = sprintf('supervisorctl %s %s %s', join(' ', $args), escapeshellarg($verb), escapeshellarg($service));
            unset($output);

            $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));
        }
    }
}
