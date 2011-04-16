<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo;

use Symfony\Component\Process\Process;

// @codeCoverageIgnoreStart
/**
 * Notifies builds via DBus.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DBusNotifier extends Notifier
{
    public function __construct($display = "", $format = "[%STATUS%]\n%message%\n%author%")
    {
        $this->display = $display;
        $this->format  = $format;
    }

    public function notify(Commit $commit)
    {
        // first, try with the notify-send program
        $process = new Process(sprintf('%s notify-send "%s" "%s"', $this->display, $commit->getProject()->getName(), $this->format($this->format, $commit)));
        $process->setTimeout(2);
        $process->run();
        if ($process->getExitCode() <= 0) {
            return;
        }

        // then, try dbus-send?
        $process = new Process(sprintf('dbus-send --print-reply --dest=org.freedesktop.Notifications /org/freedesktop/Notifications org.freedesktop.Notifications.Notify string:"sismo" int32:0 string:"" string:"%s" string:"%s" array:string:"" dict:string:"" int32:-1', $commit->getProject()->getName(), $this->format($this->format, $commit)));
        $process->setTimeout(2);
        $process->run();
        if ($process->getExitCode() <= 0) {
            return;
        }
    }
}
// @codeCoverageIgnoreEnd
