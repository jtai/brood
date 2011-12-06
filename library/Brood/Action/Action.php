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

use Brood\Config\Config,
    Brood\Log\Logger;

/**
 * Action interface
 *
 * Classes implementing actions to be executed by Drones must implement this
 * interface.
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
interface Action
{
    public function setContext(\GearmanJob $job, Config $config, $actionIndex, Logger $logger);
    public function execute();
}
