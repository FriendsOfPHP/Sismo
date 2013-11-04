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

use Sismo\Notifier\Notifier;
use Sismo\Commit;

/**
 * Delivers a connection via socket to the IRC server.
 * Extends Sismo\Notifier\Notifier for notifying users via Irc
 *
 * @package IRCBot
 * @subpackage Library
 * @author Daniel Siepmann <Daniel.Siepmann@wfp2.com>
 * @author Brent Shaffer <bshafs@gmail.com>
 */
class IrcNotifier extends Notifier
{
    private $server;
    private $port;
    private $nick;
    private $channel;
    private $format;

    /**
     * Example:
     *   // basic usage
     *   $irc = new Sismo\Contrib\IrcNotifier('#mychannel');
     *
     *   // more advanced usage
     *   $irc = new Sismo\Contrib\IrcNotifier('#mychannel', 'sismo-bot', 'chat.mysite.com', '6668');
     */
    public function __construct($channel, $nick = 'Sismo', $server = 'irc.freenode.com', $port = 6667, $format = '[%STATUS%] %name% %short_sha% -- %message% by %author%')
    {
        $this->server = $server;
        $this->port = $port;
        $this->nick = $nick;
        $this->channel = $channel;
        $this->format = $format;
    }

    public function notify(Commit $commit)
    {
        $old = error_reporting(0);
        $this->connect();
        $channels = explode(',', $this->channel);
        $this->join($channels);
        foreach ($channels as $channel) {
            $this->say($channel, $this->format($this->format, $commit));
        }
        $this->disconnect();
        error_reporting($old);
    }

    private function say($channel, $message)
    {
        $this->sendData(sprintf('PRIVMSG %s :%s', $channel, $message));
    }

    private function connect()
    {
        $this->socket = fsockopen($this->server, $this->port);
        if (!$this->isConnected()) {
            throw new \RuntimeException('Unable to connect to server via fsockopen with server: "' . $this->server . '" and port: "' . $this->port . '".');
        }
        // USER username hostname servername :realname
        $this->sendData(sprintf('USER %s Sismo Sismo :%s', $this->nick, $this->nick));
        $this->sendData(sprintf('NICK %s', $this->nick));
    }

    private function disconnect()
    {
        if ($this->socket) {
            return fclose($this->socket);
        }

        return false;
    }

    private function sendData($data)
    {
        return fwrite($this->socket, $data . "\r\n");
    }

    private function isConnected()
    {
        if (is_resource($this->socket)) {
            return true;
        }

        return false;
    }

    private function join($channel)
    {
        foreach ((array) $channel as $chan) {
            $this->sendData(sprintf('JOIN %s', $chan));
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
