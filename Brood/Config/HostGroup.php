<?php

namespace Brood\Config;

class HostGroup
{
    protected $xml;

    public function __construct($xml)
    {
        $this->xml = $xml;
    }

    public function getName()
    {
        if (isset($this->xml['name'])) {
            return (string) $this->xml['name'];
        }
    }

    public function getHosts()
    {
        $hosts = array();
        foreach ($this->xml->host as $host) {
            $hosts[(string) $host] = $host->attributes();
        }
        return $hosts;
    }
}
