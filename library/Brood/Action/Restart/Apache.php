<?php

namespace Brood\Action\Restart;

class Apache extends Init
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
