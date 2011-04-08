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
 * Notifies builds via a XMPP server.
 *
 * This notifier needs the XMPPHP library to be required in your configuration.
 *
 *    require '/path/to/XMPPHP/XMPP.php';
 *
 * Download it at http://code.google.com/p/xmpphp
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class XmppNotifier extends Notifier
{
    private $format;
    private $host;
    private $port;
    private $username;
    private $password;
    private $server;
    private $recipient;

    public function __construct($host, $port, $server, $username, $password, $recipient, $format = '[%STATUS%] %name% %short_sha% -- %message% by %author%')
    {
        $this->host = $host;
        $this->port = $port;
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
        $this->recipient = $recipient;
        $this->format = $format;
    }

    public function notify(Commit $commit)
    {
        $old = error_reporting(0);
        $conn = new \XMPPHP_XMPP($this->host, $this->port, $this->username, $this->password, 'sismo', $this->server);
        $conn->connect();
        $conn->processUntil('session_start');
        $conn->presence();
        foreach (explode(',', $this->recipient) as $user) {
            $conn->message($user, $this->format($this->format, $commit));
        }
        $conn->disconnect();
        error_reporting($old);
    }
}
// @codeCoverageIgnoreEnd
