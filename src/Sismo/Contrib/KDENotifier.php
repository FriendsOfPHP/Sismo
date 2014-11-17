<?php

/*
 * This file is part of the Sismo utility.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Contrib;

use Symfony\Component\Process\Process;
use Sismo\Notifier\Notifier;
use Sismo\Commit;

// @codeCoverageIgnoreStart
/**
 * Notifies builds via knotify or kdialog.
 *
 * @author Igor Gavrilov <mytholog@yandex.ru>
 */
class KDENotifier extends Notifier
{
    protected $titleFormat;
    protected $messageFormat;

    public function __construct($titleFormat = '', $messageFormat = '')
    {
        $this->titleFormat = $titleFormat;
        $this->messageFormat = $messageFormat;
    }

    public function notify(Commit $commit)
    {
        // first, try with the kdialog program
        $process = new Process(sprintf('kdialog --title "%s" --passivepopup "%s" 5', $this->format($this->titleFormat, $commit), $this->format($this->messageFormat, $commit)));
        $process->setTimeout(2);
        $process->run();
        if ($process->isSuccessful()) {
            return;
        }

        // then, try knotify
        $process = new Process(sprintf('dcop knotify default notify eventname "%s" "%s" "" "" 16 2 ', $this->format($this->titleFormat, $commit), $this->format($this->messageFormat, $commit)));
        $process->setTimeout(2);
        $process->run();
        if ($process->isSuccessful()) {
            return;
        }
    }
}
// @codeCoverageIgnoreEnd
