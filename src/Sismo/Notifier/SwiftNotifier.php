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

use Sismo\Notifier\MailNotifier;

class SwiftNotifier extends MailNotifier
{
    public function mail($to, $subject, $messageBody, $headers = '', $params = '')
    {
        // Sendmail
        $transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');

        // Create the Mailer using your created Transport
        $mailer = Swift_Mailer::newInstance($transport);

        // Create a message
        $message = Swift_Message::newInstance($subject)
          ->setFrom(array('cordoval@gmail.com' => 'Luis Cordova (Sismo)'))
          ->setTo(array($to))
          ->setBody($messageBody)
          ;

        // Send the message
        $result = $mailer->send($message);
    }
}
