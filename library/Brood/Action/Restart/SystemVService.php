<?php

namespace Brood\Action\Restart;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

class SystemVService extends AbstractAction
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

        $command = sprintf('/etc/init.d/%s %s 2>&1', $this->service, $verb);
        unset($output);

        $this->exec($command, $output, $return_var);
    }
}
