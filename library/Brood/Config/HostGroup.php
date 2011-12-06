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
 * Class representing a hostgroup element
 *
 * @category   Brood
 * @package    Brood_Config
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
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

    public function getAttributes()
    {
        return $this->xml->attributes();
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
