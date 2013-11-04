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
    /**
     * The server you want to connect to.
     * @var string
     */
    private $server;

    /**
     * The port of the server you want to connect to.
     * @var integer
     */
    private $port;

    /**
     * The name of the bot
     * @var string
     */
    private $nick;

    /**
     * The channel(s) or username(s) the bot should notify
     * This can be a comma-delimited array
     * @var string
     */
    private $channel;

    /**
     * Message format with predefined place holders
     * @var string
     * @see Sismo\Notifier\Notifier::getPlaceholders() for known place holders
     */
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

    /**
     * Establishs the connection to the server.
     */
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

    /**
     * Disconnects from the server.
     */
    private function disconnect()
    {
        if ($this->socket) {
            return fclose($this->socket);
        }

        return false;
    }

    /**
     * Interaction with the server.
     * For example, send commands or some other data to the server.
     */
    private function sendData($data)
    {
        return fwrite($this->socket, $data . "\r\n");
    }

    /**
     * Check wether the connection exists.
     */
    private function isConnected()
    {
        if (is_resource($this->socket)) {
            return true;
        }

        return false;
    }

    /**
     * Join a channel or array of channels
     */
    private function join($channel)
    {
        foreach ((array) $channel as $chan) {
            $this->sendData(sprintf('JOIN %s', $chan));
        }
    }

    /**
     * Close the connection.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
