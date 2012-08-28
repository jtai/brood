<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Action\Build;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

/**
 * Run make with specified targets.
 *
 * The targets configuration should simply be a space-separated
 * list of the targets that should be passed to make.
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class Make extends AbstractAction
{
    public function execute()
    {
        $this->chdir($this->getRequiredParameter('directory'));

        $targets = $this->getParameter('target');

        // Run make with configured targets
        $command = 'make' . empty($targets) ? '' : implode(' ', $targets);
        unset($output);

        $this->log(Logger::INFO, __CLASS__, 'Performing ' . $command);

        $this->sudo($command, $output, $return_var, (string) $this->getParameter('sudo'));
    }
}
