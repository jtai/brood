<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Action\Distribute;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

/**
 * Update a git repository
 *
 * This action does a `git pull` to update the repository. The repository
 * should already be on the right branch and have a default remote specified.
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class Git extends AbstractAction
{
    public function execute()
    {
        $this->chdir($this->getRequiredParameter('directory'));

        // Detect currently-deployed ref
        $command = 'git rev-parse HEAD';
        unset($output);

        $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));

        $this->setGlobalParameter('prev_ref', trim($output[0]));

        $this->log(Logger::INFO, __CLASS__, 'Performing git pull');

        // Use --ff-only to ensure that we don't have any local commits that aren't
        // also at the remote. Forbidding local commits helps all nodes stay in sync.
        $command = 'git pull --ff-only';
        unset($output);

        $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));

        // If the ref parameter is present, reset to that ref. This allows us to "roll
        // back" to a previous commit or tag if a deploy fails (or if the code that was
        // deployed was broken).
        $ref = (string) $this->getParameter('ref');
        if (!empty($ref)) {
            $this->log(Logger::INFO, __CLASS__, sprintf('Performing git reset to "%s"', $ref));

            $command = sprintf('git reset --hard %s', escapeshellarg($ref));
            unset($output);

            $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));

            $this->setGlobalParameter('ref', $ref);
        } else {
            // Detect ref
            $command = 'git rev-parse HEAD';
            unset($output);

            $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));

            $this->setGlobalParameter('ref', trim($output[0]));
        }

        // Detect if there are submodules, if so, update
        $command = 'git submodule status';
        unset($output);

        $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));

        if ($output) {
            $this->log(Logger::INFO, __CLASS__, 'Performing git submodule update --init --recursive');

            $command = 'git submodule update --init --recursive';
            unset($output);

            $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));
        }

        // If the clean parameter is present, clean up any build artifacts or temp files.
        $clean = $this->getParameter('clean');
        if (isset($clean[0])) {
            $this->log(Logger::INFO, __CLASS__, 'Removing untracked and ignored files and directories');

            $command = sprintf('git clean -dxf');
            unset($output);

            $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));
        }
    }
}
