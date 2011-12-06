<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Action;

use Brood\Log\Logger;

/**
 * Log an informational message: "Hello world!"
 *
 * This action is used to test communication between the Overlord and the
 * Drones when installing Brood.
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class HelloWorld extends AbstractAction
{
    public function execute()
    {
        $this->log(Logger::INFO, __CLASS__, 'Hello world!');
    }
}
