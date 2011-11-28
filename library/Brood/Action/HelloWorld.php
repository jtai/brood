<?php

namespace Brood\Action;

use Brood\Log\Logger;

class HelloWorld extends AbstractAction
{
    public function execute()
    {
        $this->log(Logger::INFO, __CLASS__, 'Hello world!');
    }
}
