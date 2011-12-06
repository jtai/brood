<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Loader
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Loader;

/**
 * Autoloader
 *
 * This autoloader only attempts to load classes in the Brood namespace.
 *
 * @category   Brood
 * @package    Brood_Loader
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class Autoloader
{
    public function autoload($class)
    {
        if (substr($class, 0, 6) != 'Brood\\') {
            return;
        }

        include dirname(dirname(__DIR__)) . '/' . str_replace('\\', '/', $class) . '.php';
    }

    public function register()
    {
        spl_autoload_register(array($this, 'autoload'));
    }
}
