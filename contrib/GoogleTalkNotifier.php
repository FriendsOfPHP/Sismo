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
 * Notifies builds via a Google Talk server.
 *
 * This notifier needs the XMPPHP library to be required in your configuration.
 *
 *    require '/path/to/XMPPHP/XMPP.php';
 *
 * Download it at http://code.google.com/p/xmpphp
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GoogleTalkNotifier extends XmppNotifier
{
    public function __construct($username, $password, $recipient, $format = '[%STATUS%] %name% %short_sha% -- %message% by %author%')
    {
        parent::__construct('talk.google.com', 5222, 'gmail.com', $username, $password, $recipient, $format);
    }
}
// @codeCoverageIgnoreEnd
