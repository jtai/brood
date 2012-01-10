<?php

namespace BroodTest\Action\Changelog;

use PHPUnit_Framework_TestCase as TestCase,
    Brood\Action\Changelog\Git as Action,
    Brood\Gearman,
    Brood\Config\Config;

class GitTest extends TestCase
{
    protected function getConfig($prev_ref, $ref)
    {
        $directory = dirname(dirname(dirname(dirname(__DIR__))));

        $config = new Config(<<<EOD
<?xml version="1.0" encoding="UTF-8" ?>
<brood>
    <action>
        <parameters>
            <directory>$directory</directory>
            <prev_ref>$prev_ref</prev_ref>
            <ref>$ref</ref>
            <diff_url>%s..%s</diff_url>
        </parameters>
    </action>
</brood>
EOD
);

        return $config;
    }

    protected function getJob($context)
    {
        $job = new Gearman\LocalGearmanJob();
        $job->setContext($context);
        $job->setDataCallback(array($this, 'onData'));
        return $job;
    }

    public function onData(\GearmanTask $task, $context)
    {
        list($type, $data) = Gearman\Util::decodeData($task->data());
        if ($type == 'setConfig') {
            list($param, $value) = $data;
            $context->setParameter($param, $value);
        }
    }

    public function testExecuteIdenticalRefs()
    {
        $action      = new Action();
        $config      = $this->getConfig('d95b2ae0a96c79890fe1da2eeb5dac4b843cf3eb', 'd95b2ae0a96c79890fe1da2eeb5dac4b843cf3eb');
        $job         = $this->getJob($config);
        $actionIndex = 0;
        $logger      = $this->getMock('Brood\Log\Logger');

        $this->assertNull($config->getParameter('changelog'));

        $action->setContext($job, $config, $actionIndex, $logger);
        $action->execute();

        $this->assertEquals('', (string) $config->getParameter('changelog'));
    }

    public function testExecuteDifferentRefs()
    {
        $action      = new Action();
        $config      = $this->getConfig('d95b2ae0a96c79890fe1da2eeb5dac4b843cf3eb', '7b70647170179b713994c6d3fbfcbe5bae59c778');
        $job         = $this->getJob($config);
        $actionIndex = 0;
        $logger      = $this->getMock('Brood\Log\Logger');

        $this->assertNull($config->getParameter('changelog'));

        $action->setContext($job, $config, $actionIndex, $logger);
        $action->execute();

        $this->assertEquals(<<<EOD
 README.md |   18 +++++++++++++++++-
 1 files changed, 17 insertions(+), 1 deletions(-)

d95b2ae0a96c7989..7b70647170179b71
EOD
, (string) $config->getParameter('changelog'));
    }
}
