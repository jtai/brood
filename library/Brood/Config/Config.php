<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Config
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Config;

/**
 * Class representing the top-level brood element
 *
 * @category   Brood
 * @package    Brood_Config
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class Config
{
    protected $xml;

    public function __construct($xml)
    {
        $this->xml = new \SimpleXmlElement($xml);
    }

    public function getXml()
    {
        return $this->xml->asXml();
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
        $hostGroups = array();
        foreach ($this->xml->hostgroup as $hostGroup) {
            $hostGroup = new HostGroup($hostGroup);
            $hostGroups[$hostGroup->getName()] = $hostGroup;
        }
        return $hostGroups;
    }

    public function getActions()
    {
        $actions = array();
        foreach ($this->xml->action as $action) {
            $actions[] = new Action($action);
        }
        return $actions;
    }

    public function getHostAliases()
    {
        $aliases = array();
        foreach ($this->xml->xpath('//host[@alias]') as $host) {
            $aliases[(string) $host] = $host['alias'];
        }
        return $aliases;
    }

    public function getParameter($param)
    {
        if (isset($this->xml->parameters[0])) {
            return $this->xml->parameters[0]->$param;
        }
    }

    public function addParameter($param, $value)
    {
        if (!isset($this->xml->parameters[0])) {
            $this->xml->addChild('parameters');
        }

        $this->xml->parameters[0]->addChild($param, $value);
    }

    public function setParameter($param, $value)
    {
        unset($this->xml->parameters[0]->$param);
        $this->addParameter($param, $value);
    }
}
