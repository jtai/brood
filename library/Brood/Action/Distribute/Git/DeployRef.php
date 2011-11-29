<?php

namespace Brood\Action\Distribute\Git;

use Brood\Action\AbstractAction;

class DeployRef extends AbstractAction
{
    public function execute()
    {
        if (!$this->chdir($this->getRequiredParameter('directory'))) {
            return;
        }

        $command = 'git pull --ff-only';
        unset($output);

        if (!$this->exec($command, $output, $return_var)) {
            return;
        }

        $ref = (string) $this->getParameter('ref');
        if (!empty($ref)) {
            $command = sprintf('git reset --hard %s', escapeshellarg($ref));
            unset($output);

            if (!$this->exec($command, $output, $return_var)) {
                return;
            }
        }

        $clean = $this->getParameter('clean');
        if (isset($clean[0])) {
            $command = sprintf('git clean -dxf');
            unset($output);

            if (!$this->exec($command, $output, $return_var)) {
                return;
            }
        }
    }
}
