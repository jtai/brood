<?php

namespace Brood\Action\Changelog;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

class Git extends AbstractAction
{
    public function execute()
    {
        $this->chdir($this->getRequiredParameter('directory'));

        $this->log(Logger::INFO, __CLASS__, 'Generating changelog');

        $command = sprintf(
            'git diff --stat=120,100 %s %s',
            escapeshellarg($this->getRequiredParameter('prev_ref')),
            escapeshellarg($this->getRequiredParameter('ref'))
        );
        unset($output);

        $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));

        $this->addGlobalParameter('changelog', join("\n", $output));
    }
}
