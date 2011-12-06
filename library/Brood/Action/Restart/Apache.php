<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Action\Restart;

/**
 * Restart the Apache web server
 *
 * This action restarts the Apache web server. You may want to do this at the
 * end of your deploy to clear the APC cache or pick up new configuration
 * settings.
 *
 * This action tries to detect the name of the init script automatically to
 * account for differences between RedHat-based and Debian-based distributions.
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class Apache extends SystemVService
{
    public function __construct()
    {
        // Redhat-based distros
        if (file_exists('/etc/init.d/httpd')) {
            $this->service = 'httpd';
        } else

        // Debian-based distros
        if (file_exists('/etc/init.d/apache2')) {
            $this->service = 'apache2';
        } else

        // ancient Debian-based distros
        if (file_exists('/etc/init.d/apache')) {
            $this->service = 'apache';
        }

        if (empty($this->service)) {
            throw new \RuntimeException('Unable to detect apache init script; tried httpd, apache2, and apache');
        }
    }
}
