<?php

/*
 * This file is part of the Sismo utility.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Contrib;

use Sismo\Notifier\Notifier;
use Sismo\Commit;

// @codeCoverageIgnoreStart
/**
 * Notifies builds via a Twitter.
 *
 * This notifier needs the TwitterOAuth library to be required in your configuration.
 *
 *    require '/path/to/TwitterOAuth/TwitterOAuth.php';
 *
 * Download it at git://github.com/abraham/twitteroauth.git
 *
 * In order to use Twitter as a notifier you will need a Twitter account.
 * Once you create a Twitter account for the notifier, you need to register a new application for your Twitter account.
 * After registering an application you will acquire the following credentials:
 *
 * consumerKey
 * consumerSecret
 * accessToken
 * accessTokenSecret
 *
 * You can find these credentials on your application's pages.
 *
 * @author Igor Gavrilov <mytholog@yandex.ru>
 */
class TwitterNotifier extends Notifier
{
    protected $consumerKey;
    protected $consumerSecret;
    protected $accessToken;
    protected $accessTokenSecret;
    protected $messageFormat;

    public function __construct($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, $messageFormat = "[%STATUS%]\n%message%\n%author%")
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->accessToken = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
        $this->messageFormat = $messageFormat;
    }

    public function notify(Commit $commit)
    {
        $conn = new \TwitterOAuth($this->consumerKey, $this->consumerSecret, $this->accessToken, $this->accessTokenSecret);
        $content = $conn->get('account/verify_credentials');
        $conn->post('statuses/update', array('status' => $this->format($this->messageFormat, $commit)));
    }
}
// @codeCoverageIgnoreEnd
