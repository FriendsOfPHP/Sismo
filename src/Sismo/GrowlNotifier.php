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

// @codeCoverageIgnoreStart
/**
 * Notifies builds via a Growl (Mac only).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GrowlNotifier extends Notifier
{
    private $application;
    private $address;
    private $notifications;
    private $password;
    private $port;
    private $registered;
    private $format;

    public function __construct($password, $application = 'sismo', $address = 'localhost', $format = "[%STATUS%]\n%message%\n%author%", $port = 9887)
    {
        $this->application   = $application;
        $this->address       = $address;
        $this->password      = $password;
        $this->format        = $format;
        $this->port          = $port;
        $this->registered    = false;
        $this->notifications = array(
            array('name' => 'Success', 'enabled' => true),
            array('name' => 'Fail', 'enabled' => true),
        );
    }

    public function notify(Commit $commit)
    {
        $this->register();

        return $this->doNotify($commit->isSuccessful() ? 'Success' : 'Fail', $commit->getProject()->getName(), $this->format($this->format, $commit));
    }

    private function register()
    {
        if (true === $this->registered) {
            return;
        }

        $this->registered = true;
        $data = '';
        $defaults = '';
        $nbDefaults = 0;
        foreach ($this->notifications as $i => $notification) {
            $data .= pack('n', strlen($notification['name'])).$notification['name'];
            if ($notification['enabled']) {
                $defaults .= pack('c', $i);
                ++$nbDefaults;
            }
        }

        // pack(Protocol version, type, app name, number of notifications to register)
        $data = pack('c2nc2', 1, 0, strlen($this->application), count($this->notifications), $nbDefaults).$this->application.$data.$defaults;

        $this->send($data);
    }

    private function doNotify($name, $title, $message)
    {
        // pack(protocol version, type, priority/sticky flags, notification name length, title length, message length. app name length)
        $data = pack('c2n5', 1, 1, 0, strlen($name), strlen($title), strlen($message), strlen($this->application)).$name.$title.$message.$this->application;

        $this->send($data);
    }

    private function send($data)
    {
        $data .= pack('H32', md5($data.$this->password));

        $fp = fsockopen('udp://'.$this->address, $this->port);
        fwrite($fp, $data);
        fclose($fp);
    }
}
// @codeCoverageIgnoreEnd
