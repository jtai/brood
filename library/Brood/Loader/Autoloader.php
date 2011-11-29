<?php

namespace Brood\Loader;

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
