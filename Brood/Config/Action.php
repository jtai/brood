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

    public function getOverlord()
    {
        return (bool) $this->xml->overlord;
    }

    public function getHostGroups()
    {
        $hostgroups = array();
        foreach ($this->xml->hostgroup as $hostgroup) {
            $hostgroups[(string) $hostgroup] = $hostgroup->attributes();
        }
        return $hostgroups;
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
