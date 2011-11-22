<?php

namespace Brood;

use Brood\Config\Config,
    Brood\Gearman;

class Drone
{
    protected $config;
    protected $hostname;

    public function __construct(Config $config, $hostname)
    {
        $this->config = $config;
        $this->hostname = $hostname;
    }

    public function run()
    {
        $worker = Gearman\Factory::workerFactory($this->config);

        $functionName = Gearman\Util::getFunctionName($this->hostname);
        $callback = array('\Brood\Action\Dispatcher', 'dispatch');
        $worker->addFunction($functionName, $callback, $this->config);

        printf("%s waiting for job\n", $this->hostname);

        // don't loop -- exit after every job and let supervisord restart us
        $worker->work();

        switch ($worker->returnCode()) {
            case \GEARMAN_SUCCESS:
                // will get here even if job returns failure
                echo "Done\n";
                break;

            case \GEARMAN_TIMEOUT:
                echo "Timeout\n";
                break;

            default:
                printf("%s (%d)\n", $worker->error(), $worker->returnCode());
                break;
        }
    }
}
