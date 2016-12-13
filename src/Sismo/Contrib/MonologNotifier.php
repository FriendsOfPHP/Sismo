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

use Sismo\Notifier\Notifier,
    Sismo\Commit,
    Psr\Log\LoggerInterface,
    Monolog\Logger;

/**
 * Notifies builds via Monolog or any other PSR logger.
 *
 * Here is a usage example of Monolog with Slack handler:
 *
 * $message = <<<MESSAGE
 *   Message: %message%
 *   Author: %author%
 *   Commit: %sha%
 * MESSAGE;
 *
 * $slack_handler = new SlackHandler(
 *   'slack_code',
 *   '#channel'
 * );
 *
 * $monolog = new Monolog\Logger('sismo', [$slack_handler]);
 * $notifier = new Sismo\Contrib\LoggerNotifier($monolog);
 *
 * @author Marsel Arduanov <arduanov@gmail.com>
 */
class MonologNotifier extends Notifier
{
    protected $logger;
    protected $messageFormat;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param string $messageFormat
     */
    public function __construct(LoggerInterface $logger, $messageFormat = '')
    {
        $this->logger = $logger;
        $this->messageFormat = $messageFormat;

        /**
         * set info level for monolog handlers
         */
        if ($logger instanceof Logger) {
            foreach ($logger->getHandlers() as $handler) {
                $handler->setLevel(Logger::INFO);
            }
        }
    }

    /**
     * @inherit
     */
    public function notify(Commit $commit)
    {
        $message = $this->format($this->messageFormat, $commit);
        $status = $this->format('%status_code%', $commit);
        return ($status == 'success') ? $this->logger->info($message) : $this->logger->critical($message);
    }
}