<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Notifier;

use Sismo\Commit;

// @codeCoverageIgnoreStart
/**
 * A base email notifier using the native mail() function.
 *
 * Here is a usage example:
 *
 * $subject = '[%status_code%] %name% (%short_sha%)';
 * $message = <<<MESSAGE
 *   Build status changed to %STATUS%.
 *
 *     commit: %sha%
 *     Author: %author%
 *
 *     %message%
 *
 *     Sismo reports:
 *
 *     %output%
 * MESSAGE;
 *
 * $emailNotifier = new Sismo\Notifier\MailNotifier('some@example.com', $subject, $message);
 *
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class MailNotifier extends Notifier
{
    protected $recipients;
    protected $subjectFormat;
    protected $messageFormat;
    protected $headers;
    protected $params;

    /**
     * Constructor.
     *
     * @param array|string $recipients
     * @param string       $subjectFormat
     * @param string       $messageFormat
     * @param string       $headers       Additional headers applied to the email.
     * @param string       $params        Additional params to be used on mail()
     */
    public function __construct($recipients, $subjectFormat = '', $messageFormat = '', $headers = '', $params = '')
    {
        $this->recipients = $recipients;
        $this->subjectFormat = $subjectFormat;
        $this->messageFormat = $messageFormat;
        $this->headers = $headers;
        $this->params = $params;
    }

    public function notify(Commit $commit)
    {
        $subject = $this->format($this->subjectFormat, $commit);
        $message = $this->format($this->messageFormat, $commit);

        return $this->sendEmail($this->recipients, $subject, $message, $this->headers, $this->params);
    }

    /**
     * Send the email.
     *
     * @param array|string $to
     * @param string       $subject
     * @param string       $message
     * @param string       $headers Additional headers to send.
     * @param string       $params  Additional params for the mailer in use.
     *
     * @return bool Whether the mail has been sent.
     */
    protected function sendEmail($to, $subject, $message, $headers = '', $params = '')
    {
        if (is_array($to)) {
            $to = implode(',', $to);
        }

        return mail($to, $subject, $message, $headers, $params);
    }
}
// @codeCoverageIgnoreEnd
