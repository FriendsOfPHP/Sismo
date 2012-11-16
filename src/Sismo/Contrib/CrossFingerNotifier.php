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
 * Notify only if necessary
 *
 * @author Tugdual Saunier <tugdual.saunier@gmail.com>
 */
class CrossFingerNotifier extends Notifier
{
    protected $notifiers;

    public function __construct(array $notifiers = array())
    {
        foreach ($notifiers as $notifier) {
            if(!$notifier instanceof Notifier) {
                throw new \InvalidArgumentException("Only Sismo\Notifier instance supported");
            }

            $this->notifiers[] = $notifier;
        }
    }

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

    protected function commitNeedNotification(Commit $commit)
    {
        if (!$commit->isSuccessful()) {
            return true;
        }

        $commits = $commit->getProject() ? $commit->getProject()->getCommits() : array();
        $previousCommit = isset($commits[1]) ? $commits[1] : false;

        return $previousCommit && $previousCommit->getStatusCode() != $commit->getStatusCode();
    }
}
