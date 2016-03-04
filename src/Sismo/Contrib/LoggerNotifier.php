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

use Psr\Log\LoggerInterface;
use Sismo\Commit;
use Sismo\Notifier\Notifier;

/**
 * Notifies builds via Monolog or any other PSR logger.
 * Logger must have info log level.
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
 *   '#channel',
 *   'Monolog',
 *   'true',
 *   'null',
 *    Logger::INFO
 * );
 *
 * $monolog = new Monolog\Logger('sismo', [$slack_handler]);
 * $notifier = new Sismo\Contrib\LoggerNotifier($monolog);
 *
 * @author Marsel Arduanov <arduanov@gmail.com>
 */
class LoggerNotifier extends Notifier
{
    protected $logger;
    protected $messageFormat;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param string          $messageFormat
     */
    public function __construct(LoggerInterface $logger, $messageFormat = '')
    {
        $this->logger = $logger;
        $this->messageFormat = $messageFormat;
    }

    /**
     * @inherit
     */
    public function notify(Commit $commit)
    {
        $message = $this->format($this->messageFormat, $commit);

        return ($commit->getStatusCode() == 'success') ? $this->logger->info($message) : $this->logger->critical($message);
    }
}
