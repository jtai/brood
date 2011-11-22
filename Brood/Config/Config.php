<?php

namespace Brood\Config;

class Config
{
    protected $xml;

    public function __construct($filename)
    {
        $this->xml = new \SimpleXmlElement($filename, 0, true);
    }

    public function getGearmanTimeout()
    {
        if (isset($this->xml->gearman[0]['timeout'])) {
            return (int) $this->xml->gearman[0]['timeout'];
        }
    }

    public function getGearmanServers()
    {
        $servers = array();
        if (isset($this->xml->gearman[0])) {
            foreach ($this->xml->gearman[0]->server as $server) {
                $servers[(string) $server] = $server->attributes();
            }
        }
        return $servers;
    }

    public function getHostGroups()
    {
        $hostgroups = array();
        foreach ($this->xml->hostgroup as $hostgroup) {
            $hostgroup = new HostGroup($hostgroup);
            $hostgroups[$hostgroup->getName()] = $hostgroup;
        }
        return $hostgroups;
    }

    public function getActions()
    {
        $actions = array();
        foreach ($this->xml->action as $action) {
            $actions[] = new Action($action);
        }
        return $actions;
    }
}
