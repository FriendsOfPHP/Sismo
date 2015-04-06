<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Contrib;

use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;
use Sismo\Commit;
use Sismo\Notifier\Notifier;

// @codeCoverageIgnoreStart
/**
 * Notifies builds via the best notifier available on the system (Mac, Linux or Windows).
 *
 * @author Loïck Piera <pyrech@gmail.com>
 */
class JoliNotifier extends Notifier
{
    private $format;
    private $notifier;

    public function __construct($format = "[%STATUS%]\n%message%\n%author%")
    {
        if (!class_exists('Joli\JoliNotif\NotifierFactory')) {
            throw new \RuntimeException('This notifier requires the package jolicode/jolinotif to be installed');
        }

        $this->format   = $format;
        $this->notifier = NotifierFactory::create();
    }

    public function notify(Commit $commit)
    {
        if (!$this->notifier) {
            return;
        }

        $notification = new Notification();
        $notification->setTitle($commit->getProject()->getName());
        $notification->setBody($this->format($this->format, $commit));

        $this->notifier->send($notification);
    }
}
// @codeCoverageIgnoreEnd
