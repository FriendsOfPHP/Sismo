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
    private $port = 0;

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
     *   $irc = new Sismo\Contrib\IrcNotifier('irc.freenode.com', '6667', 'sismo-bot', '#mychannel');
     */
    public function __construct($server, $port, $nick, $channel, $format = '[%STATUS%] %name% %short_sha% -- %message% by %author%')
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
        $this->join($this->channel);
        foreach (explode(',', $this->channel) as $channel) {
            $this->say($channel, $this->format($this->format, $commit));
        }
        $this->disconnect();
        error_reporting($old);
    }

    public function say($channel, $message)
    {
        $this->sendData('PRIVMSG ' . $channel. ' :' . $message);
    }

    /**
     * Establishs the connection to the server.
     */
    public function connect()
    {
        $this->socket = fsockopen($this->server, $this->port);
        if (!$this->isConnected()) {
            throw new Exception('Unable to connect to server via fsockopen with server: "' . $this->server . '" and port: "' . $this->port . '".');
        }
        $this->sendData('USER ' . $this->nick . ' Sismo ' . $this->nick. ' :' . $this->nick);
        $this->sendData('NICK ' . $this->nick);
    }

    /**
     * Disconnects from the server.
     *
     * @return boolean True if the connection was closed. False otherwise.
     */
    public function disconnect()
    {
        if ($this->socket) {
            return fclose($this->socket);
        }
        return false;
    }

    /**
     * Interaction with the server.
     * For example, send commands or some other data to the server.
     *
     * @return int|boolean the number of bytes written, or FALSE on error.
     */
    public function sendData($data)
    {
        fwrite($this->socket, $data . "\r\n");
    }

    /**
     * Returns data from the server.
     *
     * @return string|boolean The data as string, or false if no data is available or an error occured.
     */
    public function getData()
    {
        return fgets($this->socket, 256);
    }

    /**
     * Check wether the connection exists.
     *
     * @return boolean True if the connection exists. False otherwise.
     */
    public function isConnected()
    {
        if (is_resource($this->socket)) {
            return true;
        }
        return false;
    }

    /**
     * Sets the server.
     * E.g. irc.quakenet.org or irc.freenode.org
     * @param string $server The server to set.
     */
    public function setServer($server)
    {
        $this->server = (string) $server;
    }

    /**
     * Sets the port.
     * E.g. 6667
     * @param integer $port The port to set.
     */
    public function setPort($port)
    {
        $this->port = (int) $port;
    }

    public function join($channel)
    {
        if (is_array($channel)) {
            foreach ($channel as $chan) {
                $this->sendData('JOIN ' . $chan);
            }
        } else {
            $this->sendData('JOIN ' . $channel);
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
