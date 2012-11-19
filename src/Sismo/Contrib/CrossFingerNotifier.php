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

use Sismo\Commit;
use Sismo\Notifier\Notifier;

/**
 * A cross finger notifier.
 * Launch notification on an array of Notifier instance
 * only if the commit needs it.
 *
 * @author Tugdual Saunier <tugdual.saunier@gmail.com>
 */
class CrossFingerNotifier extends Notifier
{
    protected $notifiers;

    /**
     * Constructor
     *
     * @param array|Notifier $notifiers An array or a single Notifier instance
     */
    public function __construct($notifiers = array())
    {
        if (!is_array($notifiers)) {
            $notifiers = array($notifiers);
        }

        foreach ($notifiers as $notifier) {
            if(!$notifier instanceof Notifier) {
                throw new \InvalidArgumentException("Only Sismo\Notifier instance supported");
            }

            $this->notifiers[] = $notifier;
        }
    }

    /**
     * Notifies a commit.
     *
     * @param Commit $commit Then Commit instance
     * @return Boolean whether notification has been sent or not
     */
    public function notify(Commit $commit)
    {
        if ($this->commitNeedNotification($commit)) {
            foreach ($this->notifiers as $notifier) {
                $notifier->notify($commit);
            }

            return true;
        }

        return false;
    }

    /**
     * Determines if a build needs to be notify
     * based on his status and his predecessor's one
     *
     * @param Commit $commit The commit to analyse
     * @return Boolean whether the commit need notification or not
     */
    protected function commitNeedNotification(Commit $commit)
    {
        if (!$commit->isSuccessful()) {
            return true;
        }

        //getProject()->getLatestCommit() actually contains the previous build
        $previousCommit = $commit->getProject()->getLatestCommit();

        return !$previousCommit || $previousCommit->getStatusCode() != $commit->getStatusCode();
    }
}
