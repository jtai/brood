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

        $message = sprintf(
            "User: %s\n\n%s\n\nChanged Files:\n%s",
            $this->getParameter('user'),
            $this->getParameter('message'),
            $this->getParameter('changelog')
        );

        $diffUrl = (string) $this->getParameter('diff_url');
        if (!empty($diffUrl)) {
            $message .= sprintf(
                "\n\n" . $diffUrl,
                substr($this->getParameter('prev_ref'), 0, 16),
                substr($this->getParameter('ref'), 0, 16)
            );
        }

        $headers = join("\r\n", array(
            'From: ' . $from,
        ));

        $envelopeSender = preg_match('/<(.+)>/', $from, $regs) ? $regs[1] : $from;
        $params = '-f' . $envelopeSender;

        foreach ($this->getRequiredParameter('to') as $to) {
            $this->log(Logger::INFO, __CLASS__, sprintf('Sending notification email to %s', $to));

            mail($to, $subject, $message, $headers, $params);
        }
    }
}
