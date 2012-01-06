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
 * Send an e-mail announcing the deploy
 *
 * @category   Brood
 * @package    Brood_Action
 * @copyright  Copyright (c) 2011 IGN Entertainment, Inc. (http://corp.ign.com/)
 * @license    http://www.opensource.org/licenses/mit-license.php     MIT License
 */
class Email extends AbstractAction
{
    public function execute()
    {
        $from    = (string) $this->getRequiredParameter('from');
        $subject = (string) $this->getRequiredParameter('subject');

        $body = array();

        $user = (string) $this->getParameter('user');
        if (!empty($user)) {
            $body[] = sprintf('User: %s', $user);
        }

        $message = (string) $this->getParameter('message');
        if (!empty($message)) {
            $body[] = $message;
        }

        $changelog = (string) $this->getParameter('changelog');
        if (!empty($changelog)) {
            $body[] = sprintf("Changed Files:\n%s", $changelog);
        }

        if ($this->getParameter('skip_if_no_changes') && empty($changelog)) {
            return;
        }

        $body = join("\n\n", $body);

        $headers = join("\r\n", array(
            'From: ' . $from,
        ));

        $envelopeSender = preg_match('/<(.+)>/', $from, $regs) ? $regs[1] : $from;
        $params = '-f' . $envelopeSender;

        foreach ($this->getRequiredParameter('to') as $to) {
            $this->log(Logger::INFO, __CLASS__, sprintf('Sending notification email to %s', $to));

            mail($to, $subject, $body, $headers, $params);
        }
    }
}
