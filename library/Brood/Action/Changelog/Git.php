<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Action\Changelog;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

/**
 * Generate a summary of changed files
 *
 * This action uses `git diff` to generate a summary of changed files from the
 * currently-deployed ref and the ref being deployed. The changelog is added to
 * the in-memory config and is available for subsequent Announce actions.
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

        $this->log(Logger::INFO, __CLASS__, 'Generating changelog');

        if ($this->getRequiredParameter('prev_ref') == $this->getRequiredParameter('ref')) {
            $this->setGlobalParameter('changelog', '');
        }

        $command = sprintf(
            'git diff --stat=120,100 %s %s',
            escapeshellarg($this->getRequiredParameter('prev_ref')),
            escapeshellarg($this->getRequiredParameter('ref'))
        );
        unset($output);

        $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));

        $diffUrl = (string) $this->getParameter('diff_url');
        if (!empty($diffUrl)) {
            $output .= "\n\n";
            $output .= sprintf(
                $diffUrl,
                substr($this->getRequiredParameter('prev_ref'), 0, 16),
                substr($this->getRequiredParameter('ref'), 0, 16)
            );
        }

        $this->setGlobalParameter('changelog', join("\n", $output));
    }
}
