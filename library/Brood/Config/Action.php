<?php

namespace Brood\Config;

class Action
{
    protected $xml;

    public function __construct($xml)
    {
        $this->xml = $xml;
    }

    public function getClass()
    {
        if (isset($this->xml['class'])) {
            return (string) $this->xml['class'];
        }
    }

    public function getAttributes()
    {
        return $this->xml->attributes();
    }

    public function getOverlord()
    {
        return (bool) $this->xml->overlord;
    }

    public function getHostGroups()
    {
        $hostGroups = array();
        foreach ($this->xml->hostgroup as $hostGroup) {
            $hostGroups[(string) $hostGroup] = $hostGroup->attributes();
        }
        return $hostGroups;
    }

    public function getHosts()
    {
        $hosts = array();
        foreach ($this->xml->host as $host) {
            $hosts[(string) $host] = $host->attributes();
        }
        return $hosts;
    }

    public function getParameters()
    {
        return $this->xml->parameters[0];
    }
}
