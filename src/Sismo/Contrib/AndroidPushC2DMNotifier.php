<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo\Notifier;

use Sismo\Commit;

// @codeCoverageIgnoreStart
/**
 * A base C2DM (Cloud 2 Device Messaging) function that will send a small push text message to an
 *  Android device via Google service.
 *
 * Here is a usage example:
 *
 * $messageFormat = '[%status_code%] %name% (%short_sha%)';
 * $c2dmNotifier = new \Sismo\Notifier\AndroidPushC2DMNotifier('C2DM_PUSH_USER', 'C2DM_PUSH_PASSWORD',
 *      'DEVICE_REGISTRATION_ID', $messageFormat, 'SismoNotifier');
 *
 *
 * @author Michael Kliewe <info@phpgangsta.de>
 */
class AndroidPushC2DMNotifier extends Notifier
{
    protected $authCode;
    protected $deviceRegistrationId;
    protected $messageFormat;
    protected $msgType;

    /**
     * Constructor
     *
     * @param string $username
     * @param string $password
     * @param string $deviceRegistrationId
     * @param string $messageFormat
     * @param string $source
     * @param string $msgType
     * @param string $service
     */
    public function __construct($username, $password, $deviceRegistrationId, $messageFormat = '',
                                $source = 'Company-AppName-Version', $msgType = 'SismoNotifierMsgType', $service='ac2dm')
    {
        $this->authCode             = $this->getGoogleAuthCodeHelper($username, $password, $source, $service);
        $this->deviceRegistrationId = $deviceRegistrationId;
        $this->messageFormat        = $messageFormat;
        $this->msgType              = $msgType;
    }

    public function notify(Commit $commit)
    {
        $message = $this->format($this->messageFormat, $commit);

        $headers = array('Authorization: GoogleLogin auth=' . $this->authCode);
        $data = array(
            'registration_id' => $this->deviceRegistrationId,
            'collapse_key'    => $this->msgType,
            'data.message'    => $message
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://android.apis.google.com/c2dm/send");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        curl_close($ch);

        // Check the response. If the exact error message is needed it can be parsed here
        $responseArray = preg_split('/=/', $response);
        if (!isset($responseArray[0]) || !isset($responseArray[1])) {
            return false;
        }
        if (strtolower($responseArray[0]) == 'error') {
            return false;
        }
        return true;
    }

    public function getGoogleAuthCodeHelper($username, $password, $source='Company-AppName-Version', $service='ac2dm')
    {
        $ch = curl_init();
        if(!$ch){
            return false;
        }

        curl_setopt($ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
        $postFields = "accountType=" . urlencode('HOSTED_OR_GOOGLE')
            . "&Email=" . urlencode($username)
            . "&Passwd=" . urlencode($password)
            . "&source=" . urlencode($source)
            . "&service=" . urlencode($service);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);

        curl_close($ch);

        if (strpos($response, '200 OK') === false) {
            return false;
        }

        // find the auth code
        preg_match("/(Auth=)([\w|-]+)/", $response, $matches);

        if (!$matches[2]) {
            return false;
        }

        return $matches[2];
    }

}
// @codeCoverageIgnoreEnd
