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
 * Abstract action class for actions that restart a SystemV service
 *
 * Most implementing classes should only need to specify a value for $service.
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
abstract class SystemVService extends AbstractAction
{
    /**
     * Name of the script in /etc/init.d to invoke
     *
     * @var string
     */
    protected $service;

    public function execute()
    {
        $verb = (string) $this->getParameter('verb');
        if (empty($verb)) {
            $verb = 'restart';
        }

        if ($verb == 'stop') {
            $presentParticiple = 'stopping';
        } else {
            $presentParticiple = $verb . 'ing';
        }

        $this->log(Logger::INFO, __CLASS__, sprintf('%s %s', ucfirst($presentParticiple), $this->service));

        // disable RedHat's colorized init script output
        putenv('BOOTUP=nocolor');

        $command = sprintf('/etc/init.d/%s %s 2>&1', $this->service, $verb);
        unset($output);

        $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));
    }
}
