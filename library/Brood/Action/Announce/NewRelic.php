<?php
/**
 * Brood
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */

namespace Brood\Action\Announce;

use Brood\Action\AbstractAction,
    Brood\Log\Logger;

/**
 * Makes a request to New Relic's deployment API to note that a deploy occurred
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class NewRelic extends AbstractAction
{
    const NEW_RELIC_URL = 'https://rpm.newrelic.com/deployments.xml';

    public function __construct()
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required');
        }
    }

    public function execute()
    {
        $apikey = (string) $this->getRequiredParameter('api_key');
        $headers = array('x-api-key:' . $apikey);

        $headers = array();

        $changelog = (string) $this->getParameter('changelog');
        if (!empty($changelog)) {
            $headers['deployment[changelog]'] = $changelog;
        }

        $description = (string) $this->getParameter('message');
        if (!empty($description)) {
            $headers['deployment[description]'] = $description;
        }

        $revision = (string) $this->getParameter('ref');
        if (!empty($revision)) {
            $headers['deployment[revision]'] = $revision;
        }

        $user = (string) $this->getParameter('user');
        if (!empty($user)) {
            $headers['deployment[user]'] = $user;
        }

        $notified = false;

        $names = $this->getParameter('app_name');
        if (isset($names[0])) {
            foreach ($names as $name) {
                $this->log(Logger::INFO, __CLASS__, sprintf('Sending notification to New Relic application "%s"', $name));
                $this->doRequest($headers, array_merge($post, array('deployment[app_name]' => $name)));
                $notification = true;
            }
        }

        $ids = $this->getParameter('application_id');
        if (isset($ids[0])) {
            foreach ($ids as $id) {
                $this->log(Logger::INFO, __CLASS__, sprintf('Sending notification to New Relic application "%s"', $name));
                $this->doRequest($headers, array_merge($post, array('deployment[application_id]' => $id)));
                $notification = true;
            }
        }

        if (!$notified) {
            throw new \RuntimeException(sprintf('"app_name" or "application_id" configuration parameter is required by %s', get_class($this)));
        }
    }

    protected function doRequest($headers, $post)
    {
        $ch = curl_init(self::NEW_RELIC_URL);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_exec($ch);

        if (curl_errno($ch)) {
            $this->log(Logger::WARN, __CLASS__, sprintf('%s (error %d)', curl_error($ch), curl_errno($ch)));
        }

        curl_close($ch);
    }
}
