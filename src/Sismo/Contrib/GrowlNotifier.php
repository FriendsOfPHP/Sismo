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

require_once 'Net/Growl/Autoload.php';

// @codeCoverageIgnoreStart
/**
 * Notifies builds via a Growl (Mac or Windows, but not Linux)
 *
 * Requires PEAR::Net_Growl package
 *
 * @author Laurent Laville <pear@laurent-laville.org>
 * @link   http://growl.laurent-laville.org/
 * @link   http://pear.php.net/package/Net_Growl
 */
class GrowlNotifier extends Notifier
{
    const NOTIFY_SUCCESS = 'Success';
    const NOTIFY_FAILURE = 'Fail';

    /**
     * Net_Growl instance
     * @var object
     */
    private $growl;

    /**
     * Message format with predefined place holders
     * @var string
     * @see Sismo\Notifier\Notifier::getPlaceholders() for known place holders
     */
    private $format;

    /**
     * Class constructor
     *
     * @param string $application   (optional) Identify an application by a string
     * @param array  $notifications (optional) Options to configure the
     *                              notification channels
     * @param string $password      (optional) Password to protect your Growl client
     *                              for notification spamming
     * @param array  $options       (optional) Options to configure the Growl comm.
     *                              Choose either UDP or GNTP protocol,
     *                              host URL, and more ...
     */
    public function __construct($application = 'sismo', $notifications = array(),
        $password = '', $options = array()
    ) {
        $this->format = "[%STATUS%]\n%message%\n%author%";

        $notifications = array_merge(
            // default notifications (channels Success and Fail are enabled)
            array(
                self::NOTIFY_SUCCESS => array(),
                self::NOTIFY_FAILURE => array(),
            ),
            // custom notifications
            $notifications
        );

        $this->growl = \Net_Growl::singleton(
            $application, $notifications, $password, $options
        );
    }

    /**
     * Defines the new message format
     *
     * @param string $format The message format with predefined place holders
     *
     * @return $this
     * @see Sismo\Notifier\Notifier::getPlaceholders() for known place holders
     */
    public function setMessageFormat($format)
    {
        if (is_string($format) && !empty($format)) {
            $this->format = $format;
        }

        return $this;
    }

    /**
     * Notify a project commit
     *
     * @param Sismo\Commit $commit The latest project commit
     *
     * @return bool TRUE on a succesfull notification, FALSE on failure
     */
    public function notify(Commit $commit)
    {
        try {
            $this->growl->register();

            $name = $commit->isSuccessful()
                ? self::NOTIFY_SUCCESS : self::NOTIFY_FAILURE;
            $notifications = $this->growl->getApplication()->getGrowlNotifications();

            $this->growl->publish(
                $name,
                $commit->getProject()->getName(),
                $this->format($this->format, $commit),
                $notifications[$name]
            );
        } catch (\Net_Growl_Exception $e) {
            return false;
        }

        return true;
    }
}
// @codeCoverageIgnoreEnd
