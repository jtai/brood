<?php

namespace Brood\Action\Restart;

use Brood\Action\AbstractAction;

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

        $command = sprintf('/etc/init.d/%s %s 2>&1', $this->service, $verb);

        $this->exec($command, $output, $return_var);
    }
}
